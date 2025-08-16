<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\Factory\IRestPlatform;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\IAdminUser;
use Convo\Core\IServiceDataProvider;
use Convo\Core\Publish\ServiceReleaseManager;
use Convo\Gpt\Pckg\GptPackageDefinition;

class McpServerPlatform implements IRestPlatform
{
    const PLATFORM_ID = GptPackageDefinition::NAMESPACE . '.mcp-server';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var StreamableRestHandler
     */
    private $_publicHandler;

    /**
     * @var IPlatformPublisher
     */
    private $_platformPublisher;

    /**
     * @var IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var ServiceReleaseManager
     */
    private $_serviceReleaseManager;


    public function __construct($logger, $serviceDataProvider, $serviceReleaseManager, $publicHandler)
    {
        $this->_logger = $logger;
        $this->_publicHandler = $publicHandler;
        $this->_convoServiceDataProvider    =     $serviceDataProvider;
        $this->_serviceReleaseManager       =     $serviceReleaseManager;
    }

    public function getPlatformId()
    {
        return self::PLATFORM_ID;
    }

    public function getPublicRestHandler()
    {
        return $this->_publicHandler;
    }

    public function getPlatformPublisher(IAdminUser $user, $serviceId)
    {
        if (!isset($this->_platformPublisher)) {
            $this->_platformPublisher   =   new McpServerPublisher(
                $this->_logger,
                $user,
                $serviceId,
                $this->_convoServiceDataProvider,
                $this->_serviceReleaseManager
            );
        }

        return $this->_platformPublisher;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[' . $this->getPlatformId() . ']';
    }
}
