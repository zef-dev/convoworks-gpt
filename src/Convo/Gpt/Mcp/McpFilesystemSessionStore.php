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

    private $_serviceId;

    public function __construct($logger, $basePath, $serviceId)
    {
        $this->_logger = $logger;
        $this->_basePath = $basePath;
        $this->_serviceId = $serviceId;
    }

    // create new session
    public function createSession(): string
    {
        $session_id = StrUtil::uuidV4();
        $path = $this->_getServicePath() . $session_id;
        if (false === wp_mkdir_p($path)) {
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

    public function saveSession($session): void
    {
        $this->_saveSession($session);
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

    // queues the notification (now queues full JSON-RPC message)
    public function queueEvent(string $sessionId, array $data): void
    {
        $queueFile = $this->_getQueueFile($sessionId);
        file_put_contents($queueFile, json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->pingSession($sessionId);
    }

    public function queueEvents(string $sessionId, array $events): void
    {
        $queueFile = $this->_getQueueFile($sessionId);
        $lines = array_map(function ($e) {
            return json_encode($e);
        }, $events);
        file_put_contents($queueFile, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->pingSession($sessionId);
    }

    // read next message (deletes it)
    public function nextEvent($sessionId): ?array
    {
        $queueFile = $this->_getQueueFile($sessionId);
        if (!file_exists($queueFile) || filesize($queueFile) === 0) return null;
        $handle = fopen($queueFile, 'r+');
        flock($handle, LOCK_EX);
        $event = json_decode(fgets($handle), true);
        $remaining = fread($handle, filesize($queueFile));
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $remaining);
        flock($handle, LOCK_UN);
        fclose($handle);
        return $event;
    }

    private function _getQueueFile(string $sessionId): string
    {
        return $this->_getServicePath() . $sessionId . '/queue.jsonl';
    }

    public function pingSession($sessionId): void
    {
        $session = $this->getSession($sessionId);
        $session['last_active'] = time();
        $this->_saveSession($session);
    }

    private function _getServicePath(): string
    {
        return $this->_basePath . $this->_serviceId . '/';
    }

    // PERSISTENCE
    public function getSession($sessionId): array
    {
        $path = $this->_getServicePath() . $sessionId . '.json';
        if (!is_file($path)) {
            throw new DataItemNotFoundException('Session file [' . $path . '] not found');
        }

        $session = json_decode(file_get_contents($path), true);
        if (empty($session)) {
            throw new \RuntimeException('Failed to decode session file: ' . $path);
        }

        return $session;
    }

    private function _saveSession($session)
    {
        $path = $this->_getServicePath() . $session['session_id'] . '.json';
        $jsonData = json_encode($session, JSON_PRETTY_PRINT);
        file_put_contents($path, $jsonData);
    }


    // UTIL


    /**
     * Deletes session files and folders that have been inactive for the given time (in seconds).
     *
     * @param int $inactiveTime Seconds of inactivity before deletion.
     * @return int Number of deleted sessions.
     */
    public function cleanupInactiveSessions(int $inactiveTime): int
    {
        $deleted = 0;
        $files = glob($this->_getServicePath() . '*.json');
        foreach ($files as $file) {
            $session = json_decode(file_get_contents($file), true);
            if (!$session || !isset($session['last_active'])) {
                continue;
            }
            if ($session['last_active'] < time() - $inactiveTime) {
                $sessionId = $session['session_id'];
                // Delete session file
                @unlink($file);
                // Delete queue file
                $queueFile = $this->_getQueueFile($sessionId);
                if (is_file($queueFile)) {
                    @unlink($queueFile);
                }
                // Delete session folder
                $sessionFolder = $this->_getServicePath() . $sessionId;
                if (is_dir($sessionFolder)) {
                    // Remove all files in folder
                    $folderFiles = glob($sessionFolder . '/*');
                    foreach ($folderFiles as $f) {
                        @unlink($f);
                    }
                    @rmdir($sessionFolder);
                }
                $deleted++;
                $this->_logger->info("Deleted inactive session: $sessionId");
            }
        }
        return $deleted;
    }

    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
