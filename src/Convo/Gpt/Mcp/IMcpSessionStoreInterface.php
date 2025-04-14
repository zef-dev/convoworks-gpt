<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\DataItemNotFoundException;

interface IMcpSessionStoreInterface
{
    /**
     * Creates a new session and returns the session ID.
     *
     * @return string UUID or unique session identifier
     */
    public function createSession(): string;

    /**
     * Throws DataItemNotFoundException if the session is not valid or does not exist.
     *
     * @param string $sessionId
     * @return void
     * @throws DataItemNotFoundException
     */
    public function verifySessionExists(string $sessionId): void;

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
