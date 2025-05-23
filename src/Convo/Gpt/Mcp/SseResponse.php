<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\Workflow\IConvoResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SseResponse implements IConvoResponse
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    private $_sessionId;

    /**
     * @var McpSessionManager
     */
    private $_mcpSessionManager;

    public function __construct($sessionId, $mcpSessionManager)
    {
        $this->_sessionId = $sessionId;
        $this->_mcpSessionManager = $mcpSessionManager;
        $this->_logger = new NullLogger();
    }

    public function sendEvent($event, $data): void
    {
        $this->_mcpSessionManager->enqueueEvent($this->_sessionId, $event, json_encode($data));
    }



    public function addText($text, $append = false): void {}


    public function getText() {}


    public function setShouldEndSession($shouldEndSession)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function shouldEndSession(): bool
    {
        throw new \RuntimeException('Not implemented');
    }

    public function isEmpty(): bool
    {
        throw new \RuntimeException('Not implemented');
    }

    public function isSsml(): bool
    {
        throw new \RuntimeException('Not implemented');
    }

    public function addRepromptText($text, $append = false) {}

    public function getRepromptText() {}

    public function getTextSsml()
    {
        throw new \RuntimeException('Not implemented');
    }

    public function getRepromptTextSsml()
    {
        throw new \RuntimeException('Not implemented');
    }

    public function getPlatformResponse()
    {
        return [];
    }


    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
