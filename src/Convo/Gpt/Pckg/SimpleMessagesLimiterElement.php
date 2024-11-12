<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IMessages;

class SimpleMessagesLimiterElement extends AbstractWorkflowContainerComponent implements IConversationElement, IMessages
{


    private $_messages = [];


    /**
     * @var IConversationElement[]
     */
    private $_messagesDefinition = [];

    public function __construct($properties)
    {
        parent::__construct($properties);

        if (isset($properties['message_provider'])) {
            foreach ($properties['message_provider'] as $element) {
                $this->_messagesDefinition[] = $element;
                $this->addChild($element);
            }
        }
    }

    public function registerMessage($message)
    {
        $this->_messages[] = $message;
    }

    public function getMessages()
    {
        return $this->_messages;
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $this->_messages = [];
        foreach ($this->_messagesDefinition as $elem) {
            $elem->read($request, $response);
        }

        // TRUNCATE
        $truncated = $this->_truncate(
            $this->getMessages(),
            $this->evaluateString($this->_properties['max_count']),
            $this->evaluateString($this->_properties['truncate_to'])
        );

        $this->_logger->debug('Got messages after truncation [' . print_r($truncated, true) . ']');

        /** @var \Convo\Gpt\IMessages $container */
        $container = $this->findAncestor('\Convo\Gpt\IMessages');

        foreach ($truncated as $message) {
            $container->registerMessage($message);
        }
    }

    /**
     * Reduces array size to the specified number if length is greater than max. It cuts off from the beginning.
     *
     * @param array $messages Array of messages to potentially truncate.
     * @param int $max Maximum allowed length of the array.
     * @param int $to Number of items to keep in the array if truncation is necessary.
     * @return array Truncated or original array.
     */
    private function _truncate($messages, $max, $to)
    {
        $count = count($messages);
        $this->_logger->debug("Truncating messages [$count] to [$to] max [$max]");

        if ($count > $max) {
            // Calculate the number of items to remove from the start
            $offset = $count - $to;
            // Use array_slice to return the desired portion of the array
            return array_slice($messages, $offset, $to);
        }

        return $messages;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[]';
    }
}
