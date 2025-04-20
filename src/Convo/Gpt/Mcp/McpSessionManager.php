<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\DataItemNotFoundException;
use Psr\Log\LoggerInterface;

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


    public function __construct($logger, $sessionStore)
    {
        $this->_logger          =   $logger;
        $this->_sessionStore    =   $sessionStore;
    }


    // MESSAGE
    // check if valid session
    public function getActiveSession($sessionId): array
    {
        $session = $this->_sessionStore->getSession($sessionId);
        if ($session['status'] !== IMcpSessionStoreInterface::SESSION_STATUS_INITIALISED) {
            throw new DataItemNotFoundException('No active session not found: ' . $sessionId);
        }
        if ($session['last_active'] < time() - CONVO_GPT_MPC_SESSION_TIMEOUT) {
            throw new DataItemNotFoundException('Session expired: ' . $sessionId);
        }
        return $session;
    }

    // queues the notification
    public function enqueueEvent($sessionId, $event, $data): void
    {
        $this->_sessionStore->queueEvent($sessionId, ['event' => $event, 'data' => $data]);
    }



    // SSE
    // creates new session
    public function startSession(): string
    {
        set_time_limit(0);
        ignore_user_abort(true);
        ob_implicit_flush(1);
        while (ob_get_level()) ob_end_clean();

        $session_id     =   $this->_sessionStore->createSession();

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header("Mcp-Session-Id: $session_id");
        header("X-Mcp-Session-Id: $session_id");
        flush();

        echo ": connected\n\n";
        flush();
        $this->_logger->info("New session started: $session_id");
        // usleep(100000);
        return $session_id;
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

    // actually send event
    public function streamEvent($sessionId, $event, $data): void
    {
        echo "event: $event\n";
        echo "data: " . $data . "\n\n";
        flush();
    }

    // listen for events
    public function listen($sessionId): void
    {
        $lastPing = time();

        while (!connection_aborted()) {

            // Send message if available
            $empty = true;
            if ($message = $this->_sessionStore->nextEvent($sessionId)) {
                $data = is_string($message['data']) ? $message['data'] : json_encode($message['data']);
                $this->streamEvent($sessionId, $message['event'], $data);
                $this->_logger->info("Message sent [$sessionId]: " . json_encode($message));
                $empty = false;
            }

            // Send ping if needed
            if (CONVO_GPT_MPC_PING_INTERVAL && (time() - $lastPing) >= CONVO_GPT_MPC_PING_INTERVAL) {
                $this->streamEvent($sessionId, 'ping', '{}');
                $this->_logger->debug("Ping sent [$sessionId]");
                $lastPing = time();
            }

            if ($empty) {
                usleep(CONVO_GPT_MPC_LISTEN_USLEEP);
            }
        }

        $this->_logger->info("Disconnected .. session [$sessionId]");
    }


    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
