<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\DataItemNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Util\IHttpFactory;
use Convo\Core\Util\StrUtil;
use Convo\Core\EventDispatcher\ServiceRunRequestEvent;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Rest\NotFoundException;
use Convo\Core\Rest\RequestInfo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StreamableRestHandler implements RequestHandlerInterface
{
    /**
     * @var \Convo\Core\Factory\ConvoServiceFactory
     */
    private $_convoServiceFactory;

    /**
     * @var \Convo\Core\Params\IServiceParamsFactory
     */
    private $_convoServiceParamsFactory;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var \Convo\Core\EventDispatcher\EventDispatcher
     */
    private $_eventDispatcher;

    /**
     * @var McpSessionManager
     */
    private $_mcpSessionManager;

    public function __construct(
        $logger,
        $httpFactory,
        $serviceFactory,
        $serviceParamsFactory,
        $serviceDataProvider,
        $eventDispatcher,
        $mcpSessionManager
    ) {
        $this->_logger                        =    $logger;
        $this->_httpFactory                    =    $httpFactory;
        $this->_convoServiceFactory         =    $serviceFactory;
        $this->_convoServiceParamsFactory    =    $serviceParamsFactory;
        $this->_convoServiceDataProvider    =     $serviceDataProvider;
        $this->_eventDispatcher             =   $eventDispatcher;
        $this->_mcpSessionManager             =   $mcpSessionManager;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info = new RequestInfo($request);
        $this->_logger->debug('Got info [' . $info . ']');

        if ($route = $info->route('service-run/external/convo-gpt/mcp-server/{variant}/{serviceId}')) {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');
            if ($info->get()) {
                return $this->_handleSseStream($request, $variant, $serviceId);
            } elseif ($info->post()) {
                return $this->_handlePostRequest($request, $variant, $serviceId);
            } elseif ($info->delete()) {
                return $this->_handleDeleteRequest($request, $variant, $serviceId);
            }
        }

        throw new NotFoundException('Could not map [' . $info . ']');
    }

    private function _handleDeleteRequest(ServerRequestInterface $request, $variant, $serviceId)
    {
        // $this->_logger->debug("Got headers " . json_encode($request->getHeaders(), JSON_PRETTY_PRINT));
        $session_id = $request->getHeaderLine('mcp_session_id');
        if (empty($session_id)) {
            throw new InvalidRequestException('Missing mcp-session-id header for DELETE');
        }

        try {
            $this->_mcpSessionManager->terminateSession($session_id);
            return $this->_httpFactory->buildResponse('', 204, ['Content-Type' => 'text/plain']);
        } catch (DataItemNotFoundException $e) {
            throw new NotFoundException('Session [' . $session_id . '] not found', 0, $e);
        }
    }

    private function _handleSseStream(ServerRequestInterface $request, $variant, $serviceId)
    {
        // Validate auth, service, etc. (same as before)
        $owner        =    new RestSystemUser();

        try {
            $version_id            =    $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        } catch (ComponentNotFoundException $e) {
            throw new \Convo\Core\Rest\NotFoundException('Service variant [' . $serviceId . '][' . $variant . '] not found', 0, $e);
        }

        try {
            $platform_config    =    $this->_convoServiceDataProvider->getServicePlatformConfig($owner, $serviceId, $version_id);
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service platform config [' . $serviceId . '][' . $version_id . '] not found', 0, $e);
        }

        if (!isset($platform_config[McpServerPlatform::PLATFORM_ID])) {
            throw new InvalidRequestException('Service [' . $serviceId . '] version [' . $version_id . '] is not enabled for platform [' . McpServerPlatform::PLATFORM_ID . ']');
        }

        // Authorization check (OAuth 2.1 based - simple bearer token validation for demo; integrate proper OAuth validation in production)
        // $auth = $request->getHeaderLine('Authorization');
        // if (!str_starts_with($auth, 'Bearer ') || substr($auth, 7) !== 'secret') {  // Replace with real token validation
        //     return $this->_httpFactory->buildResponse('Unauthorized', 401, ['Content-Type' => 'text/plain']);
        // }

        $this->_logger->info("Running variant [$variant] of [$serviceId]");
        // $this->_logger->debug("Got headers " . json_encode($request->getHeaders(), JSON_PRETTY_PRINT));

        $session_id = $request->getHeaderLine('mcp_session_id');
        if (empty($session_id)) {
            throw new InvalidRequestException('Missing mcp-session-id header for SSE');
        }

        // Start SSE stream
        $this->_mcpSessionManager->startSseStream($session_id);  // New method, see below
        $this->_logger->info('Exiting SSE stream for session [' . $session_id . ']');
        // The stream is started with headers sent, so return an empty response object or handle accordingly
        exit(0);  // Since headers and stream are handled inside startSseStream

        // return $this->_httpFactory->buildResponse('', 200, ['Content-Type' => 'text/event-stream']);
    }

    private function _handlePostRequest(ServerRequestInterface $request, $variant, $serviceId)
    {
        // Validate auth, service, etc. (same as before)
        $owner        =    new RestSystemUser();

        try {
            $version_id            =    $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        } catch (ComponentNotFoundException $e) {
            throw new \Convo\Core\Rest\NotFoundException('Service variant [' . $serviceId . '][' . $variant . '] not found', 0, $e);
        }

        try {
            $platform_config    =    $this->_convoServiceDataProvider->getServicePlatformConfig($owner, $serviceId, $version_id);
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service platform config [' . $serviceId . '][' . $version_id . '] not found', 0, $e);
        }

        if (!isset($platform_config[McpServerPlatform::PLATFORM_ID])) {
            throw new InvalidRequestException('Service [' . $serviceId . '] version [' . $version_id . '] is not enabled for platform [' . McpServerPlatform::PLATFORM_ID . ']');
        }

        // Authorization check (OAuth 2.1 based - simple bearer token validation for demo; integrate proper OAuth validation in production)
        // $auth = $request->getHeaderLine('Authorization');
        // if (!str_starts_with($auth, 'Bearer ') || substr($auth, 7) !== 'secret') {  // Replace with real token validation
        //     return $this->_httpFactory->buildResponse('Unauthorized', 401, ['Content-Type' => 'text/plain']);
        // }

        $this->_logger->info("Running variant [$variant] of [$serviceId]");

        $session_id = $request->getHeaderLine('mcp_session_id');
        $data = $request->getParsedBody();  // Read as JSON (single or batch)

        $this->_logger->debug("Found session id [$session_id]");
        $this->_logger->debug("Got parsed body " . json_encode($data, JSON_PRETTY_PRINT));
        // $this->_logger->debug("Got headers " . json_encode($request->getHeaders(), JSON_PRETTY_PRINT));

        if (empty($session_id)) {
            $session_id = $this->_mcpSessionManager->startSession();
        }

        $responses = $this->_mcpSessionManager->processIncoming($data, $session_id, $variant, $serviceId);

        $headers = ['Content-Type' => 'application/json'];
        if (!empty($session_id)) {
            $headers['mcp-session-id'] = $session_id;
            $headers['MCP-Protocol-Version'] = '2025-06-18';  // As per new spec
        }

        $this->_logger->info("Returning responses ... " . json_encode($responses, JSON_PRETTY_PRINT));

        return $this->_httpFactory->buildResponse(json_encode($responses), 200, $headers);
    }

    private function _handleStreamableRequest(ServerRequestInterface $request, $variant, $serviceId)
    {
        $owner        =    new RestSystemUser();

        try {
            $version_id            =    $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        } catch (ComponentNotFoundException $e) {
            throw new \Convo\Core\Rest\NotFoundException('Service variant [' . $serviceId . '][' . $variant . '] not found', 0, $e);
        }

        try {
            $platform_config    =    $this->_convoServiceDataProvider->getServicePlatformConfig($owner, $serviceId, $version_id);
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service platform config [' . $serviceId . '][' . $version_id . '] not found', 0, $e);
        }

        if (!isset($platform_config[McpServerPlatform::PLATFORM_ID])) {
            throw new InvalidRequestException('Service [' . $serviceId . '] version [' . $version_id . '] is not enabled for platform [' . McpServerPlatform::PLATFORM_ID . ']');
        }

        // Authorization check (OAuth 2.1 based - simple bearer token validation for demo; integrate proper OAuth validation in production)
        // $auth = $request->getHeaderLine('Authorization');
        // if (!str_starts_with($auth, 'Bearer ') || substr($auth, 7) !== 'secret') {  // Replace with real token validation
        //     return $this->_httpFactory->buildResponse('Unauthorized', 401, ['Content-Type' => 'text/plain']);
        // }

        $this->_logger->info("Running variant [$variant] of [$serviceId]");

        $session_id = $this->_mcpSessionManager->startSession();

        // Open input stream for reading client messages
        $input_handle = fopen('php://input', 'r');
        stream_set_blocking($input_handle, false);

        try {
            $this->_mcpSessionManager->listen($session_id, $input_handle, $request, $variant, $serviceId);
        } finally {
            fclose($input_handle);
            $this->_logger->info('Stream closed. Exiting ...');
            exit(0);
        }
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
