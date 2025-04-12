<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\ComponentNotFoundException;
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

class SSERestHandler implements RequestHandlerInterface
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
        $info    =    new RequestInfo($request);

        $this->_logger->debug('Got info [' . $info . ']');

        if ($info->get() && $route = $info->route('service-run/external/convo-gpt/mcp-server/{variant}/{serviceId}/sse')) {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');
            return $this->_handleSSERequest($request, $variant, $serviceId);
        }

        if ($info->post() && $route = $info->route('service-run/external/convo-gpt/mcp-server/{variant}/{serviceId}/sse/message')) {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');
            return $this->_handleMcpCommandRequest($request, $variant, $serviceId);
        }

        throw new NotFoundException('Could not map [' . $info . ']');
    }

    private function _handleSSERequest(ServerRequestInterface $request, $variant, $serviceId)
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

        $this->_logger->info("Running variant [$variant] of [$serviceId]");

        $session_id = $this->_mcpSessionManager->startSession();

        $url = '/service-run/external/convo-gpt/mcp-server/' . $variant . '/' . $serviceId . '/sse/message?sessionId=' . $session_id;

        $this->_mcpSessionManager->send($session_id, 'endpoint', $url);

        try {
            $this->_mcpSessionManager->listen($session_id);
        } catch (\Exception $e) {
            $this->_logger->info('Stream closed [' . $e->getMessage() . ']');
        } finally {
            exit(0);
        }
    }


    private function _handleMcpCommandRequest(ServerRequestInterface $request, $variant, $serviceId)
    {
        $owner  =    new RestSystemUser();
        $role   =    McpServerCommandRequest::SPECIAL_ROLE_MCP;
        try {
            $version_id            =    $this->_convoServiceFactory->getVariantVersion($owner, $serviceId, McpServerPlatform::PLATFORM_ID, $variant);
        } catch (ComponentNotFoundException $e) {
            throw new NotFoundException('Service variant [' . $serviceId . '][' . $variant . '] not found', 0, $e);
        }

        $service     =    $this->_convoServiceFactory->getService($owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

        $this->_logger->info("Running variant [$variant] of [$serviceId] with role [$role]");

        $data = $request->getParsedBody();
        $params = $request->getQueryParams();
        $session_id = $params['sessionId'] ?? null;

        $this->_logger->debug('Got session id [' . $session_id . '], request data [' . print_r($data, true) . ']');

        try {
            $this->_mcpSessionManager->checkSession($session_id);
        } catch (\Exception $e) {
            throw new NotFoundException('Session not found [' . $session_id . '] for service [' . $serviceId . ']', 0, $e);
        }

        $request_id     =   StrUtil::uuidV4();
        $params = $request->getQueryParams();
        $sessionId = $params['sessionId'] ?? null;
        $text_request   =   new McpServerCommandRequest($serviceId, $sessionId, $request_id, $data, $role);

        $this->_logger->debug('Got request [' . $text_request . ']');

        $text_response = new SseResponse($session_id, $this->_mcpSessionManager);
        $text_response->setLogger($this->_logger);

        try {
            $this->_logger->info('Running service instance [' . $service->getId() . '] in MCP REST Handler.');
            $service->run($text_request, $text_response);
            $this->_eventDispatcher->dispatch(
                new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant),
                ServiceRunRequestEvent::NAME
            );
        } catch (\Throwable $e) {
            $this->_eventDispatcher->dispatch(
                new ServiceRunRequestEvent(false, $text_request, $text_response, $service, $variant, $e),
                ServiceRunRequestEvent::NAME
            );
            throw $e;
        }

        $this->_logger->info('Got response [' . $text_response . ']');

        $data = $text_response->getPlatformResponse();

        $this->_logger->debug('Got MCP response [' . strval($data) . ']');

        return $this->_httpFactory->buildResponse(strval($data), IHttpFactory::HTTP_STATUS_200, ['Content-Type' => 'text/xml']);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
