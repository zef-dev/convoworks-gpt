<?php declare(strict_types=1);

namespace Convo\Gpt;


interface IMessages
{
    /**
     * @param array $message
     */
    public function registerMessage( $message);

    /**
     * Returns all messages
     * @return array
     */
    public function getMessages();

}
