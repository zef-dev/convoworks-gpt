<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Psr\Log\LoggerInterface;

/**
 * Handles writing to the output stream for SSE and bidirectional streaming.
 * Centralizes all echoing and flushing to make it testable and isolated.
 */
class StreamWriter
{
    /**
     * @var LoggerInterface
     */
    private $_logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Sends HTTP headers for the stream.
     *
     * @param array $headers Associative array of header name => value.
     */
    public function sendHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        flush();
        $this->_logger->debug('Sent headers: ' . json_encode($headers));
    }

    /**
     * Sends an SSE event.
     *
     * @param string $event Event name (e.g., 'message').
     * @param string $data JSON-encoded data.
     */
    public function sendEvent(string $event, string $data): void
    {
        echo "event: $event\n";
        echo "data: $data\n\n";
        flush();
        $this->_logger->debug("Sent SSE event [$event]: $data");
    }

    /**
     * Sends a raw message line for bidirectional streaming.
     *
     * @param string $json JSON-encoded message.
     */
    public function sendMessage(string $json): void
    {
        echo $json . "\n";
        flush();
        $this->_logger->debug("Sent message: $json");
    }

    /**
     * Sends a ping message.
     */
    public function sendPing(): void
    {
        $ping = json_encode(['jsonrpc' => '2.0', 'method' => 'ping']);
        $this->sendMessage($ping);
        $this->_logger->debug('Sent ping');
    }
}
