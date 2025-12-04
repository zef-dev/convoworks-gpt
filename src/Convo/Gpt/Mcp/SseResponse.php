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

    private $_platformResponse = [];

    public function __construct($sessionId)
    {
        $this->_sessionId = $sessionId;
        $this->_logger = new NullLogger();
    }


    public function setPlatformResponse($data)
    {
        $this->_platformResponse = $data;
    }

    public function getPlatformResponse()
    {
        return is_array($this->_platformResponse) && empty($this->_platformResponse)
            ? new \stdClass()
            : $this->_platformResponse;
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


    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[' . $this->_sessionId . ']';
    }
}
