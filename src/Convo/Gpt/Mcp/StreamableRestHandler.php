<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Psr\Log\LoggerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Convo\Core\IServiceDataProvider;
use Convo\Core\ComponentNotFoundException;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Factory\ConvoServiceFactory;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Rest\NotFoundException;
use Convo\Core\Rest\RequestInfo;
use Convo\Core\Util\IHttpFactory;


class StreamableRestHandler implements RequestHandlerInterface
{
    /**
     * @var ConvoServiceFactory
     */
    private $_convoServiceFactory;

    /**
     * @var IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var McpSessionManagerFactory
     */
    private $_mcpSessionManagerFactory;

    /**
     * @var CommandDispatcher
     */
    private $_commandDispatcher;

    /**
     * @var StreamHandler
     */
    private $_streamHandler;

    public function __construct(
        $logger,
        $httpFactory,
        $serviceFactory,
        $serviceDataProvider,
        $mcpSessionManagerFactory,
        $commandDispatcher,
        $stream
    ) {
        $this->_logger                        =    $logger;
        $this->_httpFactory                    =    $httpFactory;
        $this->_convoServiceFactory         =    $serviceFactory;
        $this->_convoServiceDataProvider    =     $serviceDataProvider;
        $this->_mcpSessionManagerFactory    =   $mcpSessionManagerFactory;
        $this->_commandDispatcher             =   $commandDispatcher;
        $this->_streamHandler                        =   $stream;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info = new RequestInfo($request);
        $this->_logger->debug('Got info [' . $info . ']');
        $this->_logger->debug('Got mpc session id [' . $request->getHeaderLine('mcp_session_id') . ']');
        $this->_logger->debug('Got request data [' . print_r($request->getParsedBody(), true) . ']');

        // Handle OAuth discovery endpoints to prevent 404s
        if ($info->get() && $info->route('.well-known/oauth-protected-resource')) {
            return $this->_httpFactory->buildResponse(json_encode([]), 200, ['Content-Type' => 'application/json']);
        }
        if ($info->get() && $info->route('.well-known/oauth-authorization-server')) {
            return $this->_httpFactory->buildResponse(json_encode([]), 200, ['Content-Type' => 'application/json']);
        }
        if ($info->post() && $info->route('register')) {
            return $this->_httpFactory->buildResponse(json_encode(['error' => 'OAuth not supported']), 400, ['Content-Type' => 'application/json']);
        }



        if ($route = $info->route('service-run/external/convo-gpt/mcp-server/{variant}/{serviceId}')) {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');

            try {
                $this->_validateApplicationPassword($request, $serviceId, $variant);
            } catch (InvalidRequestException $e) {
                // Enqueue a JSON-RPC error if needed, but for HTTP response, add WWW-Authenticate
                $headers = [
                    'Content-Type' => 'application/json',
                    'WWW-Authenticate' => 'Basic realm="MCP Server"'  // Add this to indicate Basic Auth
                ];
                return $this->_httpFactory->buildResponse(
                    json_encode(['error' => ['code' => -32000, 'message' => $e->getMessage()]]),
                    $e->getCode(),
                    $headers
                );
            }


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

    private function _validateApplicationPassword(ServerRequestInterface $request, $serviceId, $variant): void
    {
        $owner        =    new RestSystemUser();

        try {
            $version_id            =    $this->_convoServiceFactory->getVariantVersion(
                $owner,
                $serviceId,
                McpServerPlatform::PLATFORM_ID,
                $variant
            );
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service variant [' . $serviceId . '][' . $variant . '] not found', 0, $e);
        }

        try {
            $config    =    $this->_convoServiceDataProvider->getServicePlatformConfig(
                $owner,
                $serviceId,
                $version_id
            );
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service platform config [' . $serviceId . '][' . $version_id . '] not found', 0, $e);
        }

        $this->_logger->debug('Got service platform config: ' . json_encode($config, JSON_PRETTY_PRINT));

        if (!isset($config[McpServerPlatform::PLATFORM_ID]['basic_auth']) || !$config[McpServerPlatform::PLATFORM_ID]['basic_auth']) {
            // Basic auth not enabled, skip validation
            $this->_logger->info('Basic auth not enabled for service [' . $serviceId . '], skipping auth validation');
            return;
        }

        $auth = $request->getHeaderLine('Authorization');
        $this->_logger->debug('Checking auth header: ' . substr($auth, 0, 20) . '...');
        if (empty($auth) || !str_starts_with($auth, 'Basic ')) {
            $this->_logger->warning('Missing or invalid Authorization header');
            throw new InvalidRequestException('Missing or invalid Authorization header', 401);
        }

        $credentials = base64_decode(substr($auth, 6), true);
        if ($credentials === false) {
            $this->_logger->warning('Failed to decode Basic auth credentials');
            throw new InvalidRequestException('Invalid Basic auth credentials', 401);
        }

        list($username, $app_password) = explode(':', $credentials, 2);

        // Use WordPress Application Passwords authentication
        $user = wp_authenticate_application_password(null, $username, $app_password);
        if (is_wp_error($user)) {
            $this->_logger->warning('Application password authentication failed for user [' . $username . ']: ' . $user->get_error_message());
            throw new InvalidRequestException('Authentication failed: ' . $user->get_error_message(), 401);
        }

        if (!$user instanceof \WP_User) {
            $this->_logger->warning('No valid WP_User returned for [' . $username . ']');
            throw new InvalidRequestException('Invalid user', 401);
        }

        wp_set_current_user($user->ID);
        $this->_logger->info('Authenticated user [' . $user->user_login . '] with ID [' . $user->ID . '] via Application Password');
    }

    private function _handleDeleteRequest(ServerRequestInterface $request, $variant, $serviceId)
    {
        $session_id = $request->getHeaderLine('mcp_session_id');
        if (empty($session_id)) {
            throw new InvalidRequestException('Missing mcp-session-id header for DELETE');
        }

        try {
            $this->_mcpSessionManagerFactory->getSessionManager($serviceId)->terminateSession($session_id);
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
            throw new NotFoundException('Service variant [' . $serviceId . '][' . $variant . '] not found', 0, $e);
        }

        try {
            $platform_config    =    $this->_convoServiceDataProvider->getServicePlatformConfig($owner, $serviceId, $version_id);
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service platform config [' . $serviceId . '][' . $version_id . '] not found', 0, $e);
        }

        if (!isset($platform_config[McpServerPlatform::PLATFORM_ID])) {
            throw new InvalidRequestException('Service [' . $serviceId . '] version [' . $version_id . '] is not enabled for platform [' . McpServerPlatform::PLATFORM_ID . ']');
        }

        $this->_logger->info("Running variant [$variant] of [$serviceId]");

        $session_id = $request->getHeaderLine('mcp_session_id');
        if (empty($session_id)) {
            throw new InvalidRequestException('Missing mcp-session-id header for SSE');
        }

        // Start SSE stream
        $this->_streamHandler->startSse($session_id, $this->_mcpSessionManagerFactory->getSessionManager($serviceId));  // New method, see below
        $this->_logger->info('Exiting SSE stream for session [' . $session_id . ']');
        // The stream is started with headers sent, so return an empty response object or handle accordingly
        exit(0);  // Since headers and stream are handled inside startSse
    }

    private function _handlePostRequest(ServerRequestInterface $request, $variant, $serviceId)
    {
        // Validate auth, service, etc. (same as before)
        $owner        =    new RestSystemUser();

        try {
            $version_id            =    $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service variant [' . $serviceId . '][' . $variant . '] not found', 0, $e);
        }

        try {
            $platform_config    =    $this->_convoServiceDataProvider->getServicePlatformConfig($owner, $serviceId, $version_id);
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service platform config [' . $serviceId . '][' . $version_id . '] not found', 0, $e);
        }

        if (!isset($platform_config[McpServerPlatform::PLATFORM_ID])) {
            throw new InvalidRequestException('Service [' . $serviceId . '] version [' . $version_id . '] is not enabled for platform [' . McpServerPlatform::PLATFORM_ID . ']');
        }

        $this->_logger->info("Running variant [$variant] of [$serviceId]");

        $session_id = $request->getHeaderLine('mcp_session_id');
        $data = $request->getParsedBody();  // Read as JSON (single or batch)

        $this->_logger->debug("Found session id [$session_id]");
        // $this->_logger->debug("Got parsed body " . json_encode($data, JSON_PRETTY_PRINT));
        // $this->_logger->debug("Got headers " . json_encode($request->getHeaders(), JSON_PRETTY_PRINT));

        if (empty($session_id)) {
            $session_id = $this->_mcpSessionManagerFactory->getSessionManager($serviceId)
                ->startSession($data['params']['clientInfo']['name'] ?? 'unknown');
        }

        $responses = $this->_commandDispatcher->processIncoming($data, $session_id, $variant, $serviceId);

        $headers = ['Content-Type' => 'application/json'];
        if (!empty($session_id)) {
            $headers['mcp-session-id'] = $session_id;
            $headers['MCP-Protocol-Version'] = '2025-06-18';  // As per new spec
        }

        if (empty($responses)) {
            $this->_logger->debug('No response needed for notification; returning 204');
            return $this->_httpFactory->buildResponse('', 204, $headers);
        }

        $this->_logger->info("Returning responses ... " . json_encode($responses, JSON_PRETTY_PRINT));

        return $this->_httpFactory->buildResponse(json_encode($responses), 200, $headers);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
