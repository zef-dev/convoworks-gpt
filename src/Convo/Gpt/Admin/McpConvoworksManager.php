<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;

use Convo\Wp\AdminUser;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\IAdminUser;
use Convo\Core\Factory\IPackageDescriptor;
use Convo\Core\Factory\IPlatformProvider;
use Convo\Core\IServiceDataProvider;
use Convo\Gpt\Mcp\McpServerPlatform;
use Psr\Log\LoggerInterface;

class McpConvoworksManager
{


    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var IPackageDescriptor
     */
    private $_package;

    public function __construct($logger, $package, $convoServiceDataProvider)
    {
        $this->_logger                      =   $logger;
        $this->_package                     =   $package;
        $this->_convoServiceDataProvider    =   $convoServiceDataProvider;
    }

    public function isServiceEnabled($serviceId)
    {
        $user       =   new AdminUser(wp_get_current_user());
        $config     =   $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        return isset($config[McpServerPlatform::PLATFORM_ID]);
    }

    public function getServiceName($serviceId)
    {
        $user       =   new AdminUser(wp_get_current_user());
        $service    =   $this->_convoServiceDataProvider->getServiceData($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        return $service['name'];
    }

    public function enableMcp($serviceId)
    {
        $this->_logger->info('Enabling service [' . $serviceId . ']');

        $user       =   new AdminUser(wp_get_current_user());
        $publisher  =   $this->_getMcpServerPublisher($user, $serviceId);

        $config =   $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $config[McpServerPlatform::PLATFORM_ID]  =   [];
        $config[McpServerPlatform::PLATFORM_ID]['time_created'] = time();
        $config[McpServerPlatform::PLATFORM_ID]['time_updated'] = time();
        $this->_convoServiceDataProvider->updateServicePlatformConfig($user, $serviceId, $config);
        $publisher->enable();
    }

    public function updateMcp($serviceId)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @param IAdminUser $user
     * @param string $serviceId
     * @return \Convo\Core\Publish\IPlatformPublisher
     */
    private function _getMcpServerPublisher($user, $serviceId)
    {
        /** @var IPlatformProvider $package */
        $package = $this->_package->getPackageInstance();
        $platform = $package->getPlatform(McpServerPlatform::PLATFORM_ID);
        return $platform->getPlatformPublisher($user, $serviceId);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
