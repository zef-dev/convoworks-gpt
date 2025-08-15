<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Util\StrUtil;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface;

class McpSessionManager
{
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var IMcpSessionStoreInterface
     */
    private $_sessionStore;

    /**
     * @var \Convo\Core\Factory\ConvoServiceFactory
     */
    private $_convoServiceFactory;

    /**
     * @var \Convo\Core\Params\IServiceParamsFactory
     */
    private $_convoServiceParamsFactory;


    public function __construct($logger, $sessionStore, $convoServiceFactory, $convoServiceParamsFactory)
    {
        $this->_logger              =   $logger;
        $this->_sessionStore        =   $sessionStore;
        $this->_convoServiceFactory         =   $convoServiceFactory;
        $this->_convoServiceParamsFactory   =   $convoServiceParamsFactory;
    }

    // check if valid session
    public function getActiveSession($sessionId, $allowNew = false): array
    {
        $session = $this->_sessionStore->getSession($sessionId);
        $status_ok = [
            IMcpSessionStoreInterface::SESSION_STATUS_INITIALISED,
        ];
        if ($allowNew) {
            $status_ok[] = IMcpSessionStoreInterface::SESSION_STATUS_NEW;
        }
        if (!in_array($session['status'], $status_ok)) {
            throw new DataItemNotFoundException('No active session found [' . $allowNew . ']: ' . $sessionId);
        }
        if ($session['last_active'] < time() - CONVO_GPT_MCP_SESSION_TIMEOUT) {
            throw new DataItemNotFoundException('Session expired: ' . $sessionId);
        }
        return $session;
    }

    // queues the full JSON-RPC message
    public function enqueueMessage($sessionId, $message): void
    {
        $this->_sessionStore->queueEvent($sessionId, $message);
    }



    // creates new session and sets up stream headers
    public function startSession(): string
    {
        $session_id     =   $this->_sessionStore->createSession();

        $this->_logger->info("New session started: $session_id");

        return $session_id;
    }

    public function terminateSession($sessionId): void
    {
        $session = $this->_sessionStore->getSession($sessionId);
        $session['status'] = 'terminated';
        $session['last_active'] = time();
        $this->_sessionStore->saveSession($session);
        $this->_logger->info("Session terminated: $sessionId");
    }

    public function startSseStream($session_id)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if (PHP_VERSION_ID >= 80000) {
            ob_implicit_flush(true);
        } else {
            ob_implicit_flush(1);
        }

        while (ob_get_level()) ob_end_clean();

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header("mcp-session-id: $session_id");
        header('MCP-Protocol-Version: 2025-06-18');
        flush();

        echo ": connected\n\n";
        flush();
        $this->_logger->info("SSE stream started for session: $session_id");

        $this->_listenSse($session_id);
    }

    private function _listenSse($sessionId)
    {
        $lastPing = time();
        $lastSessionCheck = time();

        while (!connection_aborted()) {
            if ((time() - $lastSessionCheck) >= 1) {
                try {
                    $this->getActiveSession($sessionId, true);
                } catch (DataItemNotFoundException $e) {
                    $this->_logger->warning("Session check failed [$sessionId]: " . $e->getMessage());
                    break;
                }
                $lastSessionCheck = time();
            }

            if ($message = $this->_sessionStore->nextEvent($sessionId)) {
                $data = is_string($message['data']) ? $message['data'] : json_encode($message['data']);
                echo "event: message\n";
                echo "data: " . $data . "\n\n";
                flush();
                $this->_logger->info("SSE event sent [$sessionId]: " . json_encode($message));
            }

            if (CONVO_GPT_MCP_PING_INTERVAL && (time() - $lastPing) >= CONVO_GPT_MCP_PING_INTERVAL) {
                $ping = ['jsonrpc' => '2.0', 'method' => 'ping'];
                echo "event: message\n";
                echo "data: " . json_encode($ping) . "\n\n";
                flush();
                $this->_sessionStore->pingSession($sessionId);
                $this->_logger->debug("Ping sent [$sessionId]");
                $lastPing = time();
            }

            usleep(CONVO_GPT_MCP_LISTEN_USLEEP);
        }

        $this->_logger->info("SSE disconnected for session [$sessionId]");
    }

    public function activateSession($sessionId): array
    {
        $session = $this->_sessionStore->getSession($sessionId);
        if ($session['status'] !== IMcpSessionStoreInterface::SESSION_STATUS_NEW) {
            throw new DataItemNotFoundException('No NEW session found: ' . $sessionId);
        }

        $this->_sessionStore->initialiseSession($sessionId);

        return $session;
    }

    public function processIncoming($data, $session_id, $variant, $serviceId)
    {
        $this->getActiveSession($session_id, true);

        // Support array for batch, but since 2025-06-18 removed batching, treat as single
        if (is_array($data) && array_keys($data) === range(0, count($data) - 1)) {
            $this->_logger->warning('Batching not supported in 2025-06-18; processing first message only');
            $data = $data[0];
        }

        $owner = new RestSystemUser();
        $role = McpServerCommandRequest::SPECIAL_ROLE_MCP;
        $version_id = $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        $service = $this->_convoServiceFactory->getService($owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

        $req_id = $data['id'] ?? StrUtil::uuidV4();
        $text_request = new McpServerCommandRequest($serviceId, $session_id, $req_id, $data, $role);
        $text_response = new SseResponse($session_id, $this);  // Assuming SseResponse enqueues events
        $text_response->setLogger($this->_logger);

        try {
            $this->_logger->info('Running service instance [' . $service->getId() . '] in MCP POST Handler.');
            $service->run($text_request, $text_response);
            // $this->_eventDispatcher->dispatch(
            //     new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant),
            //     ServiceRunRequestEvent::NAME
            // );
        } catch (\Throwable $e) {
            // $this->_eventDispatcher->dispatch(
            //     new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant, $e),
            //     ServiceRunRequestEvent::NAME
            // );
            $this->_logger->error($e);
            return ['jsonrpc' => '2.0', 'id' => $req_id, 'error' => ['code' => -32603, 'message' => $e->getMessage()]];
        }
        $result = $text_response->getPlatformResponse();
        if (is_array($result) && empty($result)) {
            $this->_logger->warning('Empty result array detected; converting to empty object');
            $result = new \stdClass();
        }
        // For POST, return the result directly; notifications go to SSE
        return ['jsonrpc' => '2.0', 'id' => $req_id, 'result' => $result];
    }

    public function enqueueEvent($sessionId, $event, $data)
    {
        $this->_sessionStore->queueEvent($sessionId, ['event' => $event, 'data' => $data]);
    }

    // listen for events and handle bidirectional streaming
    public function listen($sessionId, $input_handle, ServerRequestInterface $http_request, $variant, $serviceId): void
    {
        $lastPing = time();
        $lastSessionCheck = time();
        $buffer = '';  // Buffer for partial lines

        while (!connection_aborted()) {

            // Perform session check once per second
            if ((time() - $lastSessionCheck) >= 1) {
                try {
                    $this->getActiveSession($sessionId, true);
                } catch (DataItemNotFoundException $e) {
                    $this->_logger->warning("Session check failed [$sessionId]: " . $e->getMessage());
                    break;
                }
                $lastSessionCheck = time();
            }

            // Read from input non-blockingly
            $read = fread($input_handle, 8192);
            if ($read !== false && $read !== '') {
                $buffer .= $read;
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    $line = trim($line);
                    if ($line) {
                        $incoming_message = json_decode($line, true);
                        if ($incoming_message) {
                            $this->_processIncomingMessage($incoming_message, $sessionId, $http_request, $variant, $serviceId);
                        }
                    }
                }
            }

            // Send queued messages
            while ($message = $this->_sessionStore->nextEvent($sessionId)) {
                $json = json_encode($message);
                echo $json . "\n";
                flush();
                $this->_logger->info("Message sent [$sessionId]: " . $json);
            }

            // Send ping if needed
            if (CONVO_GPT_MCP_PING_INTERVAL && (time() - $lastPing) >= CONVO_GPT_MCP_PING_INTERVAL) {
                $ping = ['jsonrpc' => '2.0', 'method' => 'ping'];
                echo json_encode($ping) . "\n";
                flush();
                $this->_sessionStore->pingSession($sessionId);
                $this->_logger->debug("Ping sent [$sessionId]");
                $lastPing = time();
            }

            usleep(CONVO_GPT_MCP_LISTEN_USLEEP);
        }

        $this->_logger->info("Disconnected .. session [$sessionId]");
    }

    private function _processIncomingMessage($message, $sessionId, ServerRequestInterface $http_request, $variant, $serviceId)
    {
        $owner  =    new RestSystemUser();
        $role   =    McpServerCommandRequest::SPECIAL_ROLE_MCP;
        $version_id = $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        $service = $this->_convoServiceFactory->getService($owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

        // Support batching
        if (is_array($message) && array_keys($message) === range(0, count($message) - 1)) {
            $responses = [];
            foreach ($message as $single_msg) {
                $req_id = $single_msg['id'] ?? StrUtil::uuidV4();
                $text_request = new McpServerCommandRequest($serviceId, $sessionId, $req_id, $single_msg, $role);
                $text_response = new SseResponse($sessionId, $this);
                $text_response->setLogger($this->_logger);
                try {
                    $service->run($text_request, $text_response);
                    // $this->_eventDispatcher->dispatch(
                    //     new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant),
                    //     ServiceRunRequestEvent::NAME
                    // );
                } catch (\Throwable $e) {
                    // $this->_eventDispatcher->dispatch(
                    //     new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant, $e),
                    //     ServiceRunRequestEvent::NAME
                    // );
                    // Enqueue error for this batch item
                    $responses[] = ['jsonrpc' => '2.0', 'id' => $req_id, 'error' => ['code' => -32603, 'message' => $e->getMessage()]];
                }
                // Responses are enqueued by the processor
            }
            // If there are errors, enqueue the batch errors
            if (!empty($responses)) {
                $this->enqueueMessage($sessionId, $responses);
            }
        } else {
            $req_id = $message['id'] ?? StrUtil::uuidV4();
            $text_request = new McpServerCommandRequest($serviceId, $sessionId, $req_id, $message, $role);
            $text_response = new SseResponse($sessionId, $this);
            $text_response->setLogger($this->_logger);
            try {
                $service->run($text_request, $text_response);
                // $this->_eventDispatcher->dispatch(
                //     new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant),
                //     ServiceRunRequestEvent::NAME
                // );
            } catch (\Throwable $e) {
                // $this->_eventDispatcher->dispatch(
                //     new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant, $e),
                //     ServiceRunRequestEvent::NAME
                // );
                // Enqueue error
                $error = ['jsonrpc' => '2.0', 'id' => $req_id, 'error' => ['code' => -32603, 'message' => $e->getMessage()]];
                $this->enqueueMessage($sessionId, $error);
            }
        }
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
