<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\Workflow\ISpecialRoleRequest;
use Convo\Core\Util\StrUtil;


class McpServerCommandRequest implements ISpecialRoleRequest
{
    const SPECIAL_ROLE_MCP = 'mcp-server';

    private $_serviceId;
    private $_sessionId;
    private $_requestId;
    private $_role;

    private $_data    =    array();

    public function __construct($serviceId, $sessionId, $requestId, $data, $role)
    {
        $this->_serviceId   =   $serviceId;
        $this->_sessionId   =   $sessionId;
        $this->_requestId   =   $requestId;
        $this->_data        =   $data;
        $this->_role        =   $role;
    }

    public function getId()
    {
        return $this->_data['id'] ?? null;
    }

    public function getMethod()
    {
        return $this->_data['method'] ?? null;
    }

    public function getSpecialRole()
    {
        return $this->_role;
    }

    public function getServiceId()
    {
        return $this->_serviceId;
    }

    public function getPlatformId()
    {
        return McpServerPlatform::PLATFORM_ID;
    }

    public function getApplicationId()
    {
        return $this->getServiceId();
    }

    public function getPlatformData()
    {
        return $this->_data;
    }

    public function getMediaTypeRequest() {}

    public function getText()
    {
        return '';
    }

    public function isEmpty()
    {
        return false;
    }

    public function getInstallationId()
    {
        return $this->getServiceId();
    }

    public function isMediaRequest()
    {
        return false;
    }

    public function isHealthCheck()
    {
        return false;
    }

    public function getIsCrossSessionCapable()
    {
        return true;
    }

    public function getAccessToken() {}

    public function isLaunchRequest()
    {
        return $this->isSessionStart();
    }

    public function getDeviceId()
    {
        return $this->getServiceId();
    }

    public function isSalesRequest()
    {
        return false;
    }

    public function isSessionEndRequest() {}

    public function isSessionStart()
    {
        return false;
    }

    public function getSessionId()
    {
        return $this->_sessionId;
    }

    public function getRequestId()
    {
        return $this->_requestId;
    }



    // UTIL
    public function __toString()
    {
        return get_class($this) . '[' . $this->getText() . '][' . $this->getServiceId() . '][' . $this->getRequestId() . '][' . $this->getSessionId() . '][' . $this->getSpecialRole() . ']';
    }
}
