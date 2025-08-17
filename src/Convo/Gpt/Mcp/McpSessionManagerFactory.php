<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Psr\Log\LoggerInterface;

class McpSessionManagerFactory
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
     * @var McpSessionManager[]
     */
    private $_instances = [];

    public function __construct($logger, $sessionStore)
    {
        $this->_logger                      =   $logger;
        $this->_sessionStore                =   $sessionStore;
    }

    public function getSessionManager($serviceId): McpSessionManager
    {
        if (!isset($this->_instances[$serviceId])) {
            $this->_logger->debug("Creating new McpSessionManager for service: $serviceId");
            $this->_instances[$serviceId] = new McpSessionManager(
                $this->_logger,
                $this->_sessionStore,
                $serviceId
            );
        }
        return $this->_instances[$serviceId];
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
