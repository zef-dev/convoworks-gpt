<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Util\StrUtil;
use Psr\Log\LoggerInterface;

class McpFilesystemSessionStore implements IMcpSessionStoreInterface
{
    /**
     * @var LoggerInterface
     */
    private $_logger;

    private $_basePath;

    public function __construct($logger, $basePath)
    {
        $this->_logger = $logger;
        $this->_basePath = $basePath;
    }

    // SSE
    // create new session
    public function createSession(): string
    {
        $session_id = StrUtil::uuidV4();
        $path = $this->_basePath . $session_id;
        if (false === mkdir($path, 0777, true)) {
            throw new \RuntimeException('Failed to create session directory: ' . $path);
        }
        $this->_logger->debug('Created new session directory: ' . $path);
        return $session_id;
    }

    // read next message (deletes it)
    public function nextEvent($sessionId): ?array
    {
        $path = $this->_basePath . $sessionId;
        $files = glob($path . '/*.json');
        if (empty($files)) {
            return null; // or throw an exception if preferred
        }
        $file = $files[0];
        $data = json_decode(file_get_contents($file), true);
        unlink($file);
        return $data;
    }

    // COMMANDS
    // returns session or throws not found exception
    public function verifySessionExists($sessionId): void
    {
        $path = $this->_basePath . $sessionId;
        if (!is_dir($path)) {
            throw new DataItemNotFoundException('Session folder [' . $path . '] not found');
        }
    }

    // queues the notification
    public function queueEvent($sessionId, $data): void
    {
        $path = $this->_basePath . $sessionId;

        // Prepare filename using microtime to ensure uniqueness
        $filename = sprintf('%s.json', microtime(true));

        // Full file path
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;

        // JSON serialize the data
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        // Write data to file
        file_put_contents($filepath, $jsonData);

        $this->_logger->debug('Wrote notification to file: ' . $jsonData);
    }



    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
