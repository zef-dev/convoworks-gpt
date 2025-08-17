<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\DataItemNotFoundException;
use Psr\Log\LoggerInterface;

class McpSessionManager
{
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var IMcpSessionStoreInterface
     */
    private $_sessionStore;

    public function __construct($logger, $sessionStore)
    {
        $this->_logger                      =   $logger;
        $this->_sessionStore                =   $sessionStore;
    }

    // check if valid session
    public function getActiveSession($sessionId, $allowNew = false): array
    {
        $session = $this->_sessionStore->getSession($sessionId);
        $status_ok = [
            IMcpSessionStoreInterface::SESSION_STATUS_INITIALISED,
        ];
        if ($allowNew) {
            $status_ok[] = IMcpSessionStoreInterface::SESSION_STATUS_NEW;
        }
        if (!in_array($session['status'], $status_ok)) {
            throw new DataItemNotFoundException('No active session found [' . $allowNew . ']: ' . $sessionId);
        }
        if ($session['last_active'] < time() - CONVO_GPT_MCP_SESSION_TIMEOUT) {
            throw new DataItemNotFoundException('Session expired: ' . $sessionId);
        }
        return $session;
    }

    // creates new session and sets up stream headers
    public function startSession($clientName): string
    {
        $session_id     =   $this->_sessionStore->createSession($clientName);

        $this->_logger->info("New session started: $session_id");

        return $session_id;
    }

    public function activateSession($sessionId): array
    {
        $session = $this->_sessionStore->getSession($sessionId);
        if ($session['status'] !== IMcpSessionStoreInterface::SESSION_STATUS_NEW) {
            throw new DataItemNotFoundException('No NEW session found: ' . $sessionId);
        }

        $this->_sessionStore->initialiseSession($sessionId);

        return $session;
    }

    public function terminateSession($sessionId): void
    {
        $session = $this->_sessionStore->getSession($sessionId);
        $session['status'] = IMcpSessionStoreInterface::SESSION_STATUS_TERMINATED;
        $session['last_active'] = time();
        $this->_sessionStore->saveSession($session);
        $this->_logger->info("Session terminated: $sessionId");
    }

    // queues the full JSON-RPC message
    public function enqueueMessage($sessionId, $message): void
    {
        $this->_sessionStore->queueEvent($sessionId, $message);
    }

    public function getSessionStore(): IMcpSessionStoreInterface
    {
        return $this->_sessionStore;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
