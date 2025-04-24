<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\DataItemNotFoundException;

interface IMcpSessionStoreInterface
{
    const SESSION_STATUS_NEW = 'NEW';
    const SESSION_STATUS_INITIALISED = 'INITIALISED';

    /**
     * Creates a new session and returns the session ID.
     *
     * @return string UUID or unique session identifier
     */
    public function createSession(): string;

    /**
     * Updates the last active time for the session.
     *
     * @param string $sessionId
     */
    public function pingSession($sessionId): void;

    /**
     * Initializes NEW session to INITIALISED.
     *
     */
    public function initialiseSession($sessionId): void;

    /**
     * Throws DataItemNotFoundException if the session does not exist.
     *
     * @param string $sessionId
     * @return array session data
     * @throws DataItemNotFoundException
     */
    public function getSession(string $sessionId): array;

    /**
     * Persists an event/message for the session.
     *
     * @param string $sessionId
     * @param array $data ['event' => ..., 'data' => ...]
     * @return void
     */
    public function queueEvent(string $sessionId, array $data): void;

    /**
     * Retrieves and removes the next queued message for the session.
     *
     * @param string $sessionId
     * @return array|null
     */
    public function nextEvent(string $sessionId): ?array;

    /**
     * Returns a string representation for debugging/logging.
     *
     * @return string
     */
    public function __toString();
}
