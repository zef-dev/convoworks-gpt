<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

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
        $this->_logger          =   $logger;
        $this->_sessionStore    =   $sessionStore;
    }


    // MESSAGE
    // check if valid session
    public function checkSession($sessionId): void
    {
        $this->_sessionStore->verifySessionExists($sessionId);
    }

    // queues the notification
    public function enqueueEvent($sessionId, $event, $data): void
    {
        $this->_sessionStore->queueEvent($sessionId, ['event' => $event, 'data' => $data]);
    }


    // SSE
    // creates new session
    public function startSession(): string
    {
        set_time_limit(0);
        ignore_user_abort(true);
        ob_implicit_flush(1);
        while (ob_get_level()) ob_end_clean();

        $session_id     =   $this->_sessionStore->createSession();

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header("Mcp-Session-Id: $session_id");
        header("X-Mcp-Session-Id: $session_id");
        flush();

        echo ": connected\n\n";
        flush();
        $this->_logger->info("New session started: $session_id");
        // usleep(100000);
        return $session_id;
    }

    // actually send event
    public function streamEvent($sessionId, $event, $data): void
    {
        echo "event: $event\n";
        echo "data: " . $data . "\n\n";
        flush();
    }

    // listen for events
    public function listen($sessionId): void
    {
        $SLEEP = 1;
        $PING_INTERVAL = 10; // seconds
        $lastPing = time();

        while (!connection_aborted()) {

            // Send message if available
            $empty = true;
            if ($message = $this->_sessionStore->nextEvent($sessionId)) {
                $data = is_string($message['data']) ? $message['data'] : json_encode($message['data']);
                $this->streamEvent($sessionId, $message['event'], $data);
                $this->_logger->info("Message sent [$sessionId]: " . json_encode($message));
                $empty = false;
            }

            // Send ping if needed
            if ((time() - $lastPing) >= $PING_INTERVAL) {
                $this->streamEvent($sessionId, 'ping', '{}');
                $this->_logger->debug("Ping sent [$sessionId]");
                $lastPing = time();
            }

            if ($empty) {
                sleep($SLEEP);
            }
        }

        $this->_logger->info("Disconnected .. session [$sessionId]");
    }


    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
