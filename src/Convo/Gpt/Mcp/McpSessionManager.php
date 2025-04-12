<?php

declare(strict_types=1);

namespace Convo\Gpt\Mcp;

use Convo\Core\Util\StrUtil;
use Psr\Log\LoggerInterface;

class McpSessionManager
{
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var McpFilesystemSessionStore
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
        $this->_sessionStore->check($sessionId);
    }

    // queues the notification
    public function accept($sessionId, $event, $data): void
    {
        $this->_sessionStore->write($sessionId, ['event' => $event, 'data' => $data]);
    }


    // SSE
    // creates new session
    public function startSession(): string
    {
        // $session_id     =   StrUtil::uuidV4();
        $session_id     =   $this->_sessionStore->new();
        return $session_id;
    }

    // actually send event
    public function send($sessionId, $event, $data): void
    {
        echo "event: $event\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }

    // listen for events
    public function listen($sessionId): void
    {
        while (!connection_aborted()) {

            if ($message = $this->_sessionStore->useMessage($sessionId)) {
                $this->send($sessionId, $message['event'], $message['data']);
                $this->_logger->info("Message sent: " . json_encode($message));
            }

            sleep(1);
        }
    }




    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
