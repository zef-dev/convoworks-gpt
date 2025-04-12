<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\IAdminUser;
use Convo\Core\Publish\AbstractServicePublisher;
use Convo\Core\Publish\IPlatformPublisher;

class McpServerPublisher extends AbstractServicePublisher
{

    public function __construct($logger, IAdminUser $user, $serviceId, $serviceDataProvider, $serviceReleaseManager)
    {
        parent::__construct($logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);
    }

    public function getPlatformId()
    {
        return McpServerPlatform::PLATFORM_ID;
    }

    public function export()
    {
        throw new \Exception('Not supported');
    }

    public function enable()
    {
        $this->_checkEnabled();

        $this->_serviceReleaseManager->initDevelopmentRelease($this->_user, $this->_serviceId, $this->getPlatformId(), 'a');
    }

    public function delete(array &$report)
    {
        //         throw new NotImplementedException('Deletion not yet implemented for ['.$this->getPlatformId().'] platform');
    }

    public function getStatus()
    {
        return ['status' => IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED];
    }
}
