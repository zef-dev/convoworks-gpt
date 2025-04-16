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

        $session = [
            'session_id' => $session_id,
            'status' => IMcpSessionStoreInterface::SESSION_STATUS_NEW,
            'created_at' => time(),
            'last_active' => time(),
        ];
        $this->_saveSession($session);

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
    public function initialiseSession($sessionId): void
    {
        $session = $this->getSession($sessionId);

        if ($session['status'] !== IMcpSessionStoreInterface::SESSION_STATUS_NEW) {
            throw new DataItemNotFoundException('No NEW session found: ' . $sessionId);
        }

        $session['status'] = IMcpSessionStoreInterface::SESSION_STATUS_INITIALISED;
        $session['last_active'] = time();

        $this->_saveSession($session);
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

        $session = $this->getSession($sessionId);
        $session['last_active'] = time();
        $this->_saveSession($session);

        $this->_logger->debug('Wrote notification to file: ' . $jsonData);
    }

    // PERSISTENCE
    public function getSession($sessionId): array
    {
        $path = $this->_basePath . $sessionId . '.json';
        if (!is_file($path)) {
            throw new DataItemNotFoundException('Session file [' . $path . '] not found');
        }

        $session = json_decode(file_get_contents($path), true);
        if (empty($session)) {
            throw new \RuntimeException('Failed to decode session file: ' . $path);
        }

        $this->_logger->debug('Loaded session: ' . json_encode($session));

        return $session;
    }

    private function _saveSession($session)
    {
        $path = $this->_basePath . $session['session_id'] . '.json';
        // JSON serialize the data
        $jsonData = json_encode($session, JSON_PRETTY_PRINT);

        // Write data to file
        file_put_contents($path, $jsonData);
    }


    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
