<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IMessages;
use Convo\Gpt\Util;

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
        $truncated = Util::truncate(
            $this->getMessages(),
            (int)$this->evaluateString($this->_properties['max_count']),
            (int)$this->evaluateString($this->_properties['truncate_to'])
        );

        $this->_logger->debug('Got messages after truncation [' . print_r($truncated, true) . ']');

        /** @var \Convo\Gpt\IMessages $container */
        $container = $this->findAncestor('\Convo\Gpt\IMessages');

        foreach ($truncated as $message) {
            $container->registerMessage($message);
        }
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[]';
    }
}
