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


    private $_basePath;

    /**
     * @var McpSessionManager[]
     */
    private $_instances = [];

    public function __construct($logger, $basePath)
    {
        $this->_logger                      =   $logger;
        $this->_basePath                    =   $basePath;
    }

    public function getSessionManager($serviceId): McpSessionManager
    {
        if (!isset($this->_instances[$serviceId])) {
            $this->_logger->debug("Creating new McpSessionManager for service: $serviceId");
            $mcp_store = new McpFilesystemSessionStore($this->_logger, $this->_basePath, $serviceId);
            $this->_instances[$serviceId] = new McpSessionManager(
                $this->_logger,
                $mcp_store
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
