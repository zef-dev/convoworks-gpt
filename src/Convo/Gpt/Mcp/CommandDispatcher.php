<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\Factory\ConvoServiceFactory;
use Convo\Core\Params\IServiceParamsFactory;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Util\StrUtil;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Dispatches incoming commands by running Convo services.
 * Extracted from McpSessionManager to isolate processing logic.
 */
class CommandDispatcher
{
    /**
     * @var ConvoServiceFactory
     */
    private $_convoServiceFactory;

    /**
     * @var IServiceParamsFactory
     */
    private $_convoServiceParamsFactory;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var McpSessionManagerFactory
     */
    private $_mcpSessionManagerFactory;

    public function __construct(
        ConvoServiceFactory $convoServiceFactory,
        IServiceParamsFactory $convoServiceParamsFactory,
        LoggerInterface $logger,
        McpSessionManagerFactory $mcpSessionManagerFactory
    ) {
        $this->_convoServiceFactory = $convoServiceFactory;
        $this->_convoServiceParamsFactory = $convoServiceParamsFactory;
        $this->_logger = $logger;
        $this->_mcpSessionManagerFactory = $mcpSessionManagerFactory;
    }

    /**
     * Processes incoming data (single or batch) and returns responses.
     *
     * @param array $data
     * @param string $sessionId
     * @param string $variant
     * @param string $serviceId
     * @return array
     */
    public function processIncoming(array $data, string $sessionId, string $variant, string $serviceId): array
    {
        $this->_mcpSessionManagerFactory->getSessionManager($serviceId)->getActiveSession($sessionId, true);

        if (array_keys($data) === range(0, \count($data) - 1)) {
            $this->_logger->warning('Batching not supported in 2025-06-18; processing first message only');
            $data = $data[0];
        }

        // Handle notifications (no 'id')
        if (!isset($data['id'])) {
            $this->_logger->debug('Processing notification [' . $data['method'] . ']; no response needed');
            $owner = new RestSystemUser();
            $role = McpServerCommandRequest::SPECIAL_ROLE_MCP;
            $version_id = $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
            $service = $this->_convoServiceFactory->getService($owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

            $text_request = new McpServerCommandRequest($serviceId, $sessionId, StrUtil::uuidV4(), $data, $role);
            $text_response = new SseResponse($sessionId);
            $text_response->setLogger($this->_logger);

            try {
                $this->_logger->info('Running service instance [' . $service->getId() . '] for notification [' . $data['method'] . ']');
                $service->run($text_request, $text_response);
            } catch (Throwable $e) {
                $this->_logger->error('Error processing notification [' . $data['method'] . ']: ' . $e->getMessage());
            }

            return []; // No response body for notifications
        }

        $owner = new RestSystemUser();
        $role = McpServerCommandRequest::SPECIAL_ROLE_MCP;
        $version_id = $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        $service = $this->_convoServiceFactory->getService($owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

        $req_id = $data['id'];
        $text_request = new McpServerCommandRequest($serviceId, $sessionId, StrUtil::uuidV4(), $data, $role);
        $text_response = new SseResponse($sessionId);
        $text_response->setLogger($this->_logger);

        try {
            $this->_logger->info('Running service instance [' . $service->getId() . '] in MCP POST Handler.');
            $service->run($text_request, $text_response);
        } catch (Throwable $e) {
            /** @phpstan-ignore-next-line */
            $this->_logger->error($e);
            return ['jsonrpc' => '2.0', 'id' => $req_id, 'error' => ['code' => -32603, 'message' => $e->getMessage()]];
        }

        $result = $text_response->getPlatformResponse();
        if (\is_array($result) && empty($result)) {
            $this->_logger->warning('Empty result array detected; converting to empty object');
            $result = new \stdClass();
        }

        return ['jsonrpc' => '2.0', 'id' => $req_id, 'result' => $result];
    }

    /**
     * Processes a single incoming message (for bidirectional streaming).
     *
     * @param array $message
     * @param string $sessionId
     * @param string $variant
     * @param string $serviceId
     */
    public function processMessage(
        array $message,
        string $sessionId,
        string $variant,
        string $serviceId
    ): void {
        $owner = new RestSystemUser();
        $role = McpServerCommandRequest::SPECIAL_ROLE_MCP;
        $version_id = $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        $service = $this->_convoServiceFactory->getService($owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

        $manager = $this->_mcpSessionManagerFactory->getSessionManager($serviceId);

        if (array_keys($message) === range(0, \count($message) - 1)) {
            $responses = [];
            foreach ($message as $single_msg) {
                $req_id = $single_msg['id'];
                $text_request = new McpServerCommandRequest($serviceId, $sessionId, StrUtil::uuidV4(), $single_msg, $role);
                $text_response = new SseResponse($sessionId);
                $text_response->setLogger($this->_logger);
                try {
                    $service->run($text_request, $text_response);
                } catch (Throwable $e) {
                    $responses[] = ['jsonrpc' => '2.0', 'id' => $req_id, 'error' => ['code' => -32603, 'message' => $e->getMessage()]];
                }
            }
            if (!empty($responses)) {
                $manager->enqueueMessage($sessionId, $responses);
            }
        } else {
            $req_id = $message['id'];
            $text_request = new McpServerCommandRequest($serviceId, $sessionId, StrUtil::uuidV4(), $message, $role);
            $text_response = new SseResponse($sessionId);
            $text_response->setLogger($this->_logger);
            try {
                $service->run($text_request, $text_response);
            } catch (Throwable $e) {
                $error = ['jsonrpc' => '2.0', 'id' => $req_id, 'error' => ['code' => -32603, 'message' => $e->getMessage()]];
                $manager->enqueueMessage($sessionId, $error);
            }
        }
    }
}
