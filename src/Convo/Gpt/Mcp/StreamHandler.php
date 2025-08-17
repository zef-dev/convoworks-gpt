<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\DataItemNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Handles streaming loops for SSE and bidirectional communication.
 * Extracted from McpSessionManager to isolate output and looping logic.
 */
class StreamHandler
{
    /**
     * @var StreamWriter
     */
    private $_streamWriter;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    public function __construct(StreamWriter $streamWriter, LoggerInterface $logger)
    {
        $this->_streamWriter = $streamWriter;
        $this->_logger = $logger;
    }

    /**
     * Starts and manages the SSE stream.
     *
     * @param string $sessionId
     * @param McpSessionManager $manager
     */
    public function startSse(string $sessionId, McpSessionManager $manager): void
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if (PHP_VERSION_ID >= 80000) {
            ob_implicit_flush(true);
        } else {
            ob_implicit_flush(1);
        }

        while (ob_get_level()) ob_end_clean();

        $headers = [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'mcp-session-id' => $sessionId,
            'MCP-Protocol-Version' => '2025-06-18'
        ];
        $this->_streamWriter->sendHeaders($headers);

        $this->_streamWriter->sendEvent('message', ': connected');

        $lastPing = time();
        $lastSessionCheck = time();

        while (!connection_aborted()) {
            if ((time() - $lastSessionCheck) >= 1) {
                try {
                    $manager->getActiveSession($sessionId, true);
                } catch (DataItemNotFoundException $e) {
                    $this->_logger->warning("Session check failed [$sessionId]: " . $e->getMessage());
                    break;
                }
                $lastSessionCheck = time();
            }

            if ($message = $manager->getSessionStore()->nextEvent($sessionId)) {
                $data = is_string($message['data']) ? $message['data'] : json_encode($message['data']);
                $this->_streamWriter->sendEvent('message', $data);
            }

            if (CONVO_GPT_MCP_PING_INTERVAL && (time() - $lastPing) >= CONVO_GPT_MCP_PING_INTERVAL) {
                $this->_streamWriter->sendPing();
                $manager->getSessionStore()->pingSession($sessionId);
                $lastPing = time();
            }

            usleep(CONVO_GPT_MCP_LISTEN_USLEEP);
        }

        $this->_logger->info("SSE disconnected for session [$sessionId]");
    }
}
