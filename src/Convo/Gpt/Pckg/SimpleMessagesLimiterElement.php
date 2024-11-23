<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Params\IServiceParamsScope;
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

    /**
     * @var IConversationElement[]
     */
    private $_truncatedFlow = [];

    public function __construct($properties)
    {
        parent::__construct($properties);

        if (isset($properties['message_provider'])) {
            foreach ($properties['message_provider'] as $element) {
                $this->_messagesDefinition[] = $element;
                $this->addChild($element);
            }
        }

        if (isset($properties['truncated_flow'])) {
            foreach ($properties['truncated_flow'] as $element) {
                $this->_truncatedFlow[] = $element;
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
        // REGISTER MESSAGES
        $this->_messages = [];
        foreach ($this->_messagesDefinition as $elem) {
            $elem->read($request, $response);
        }

        // TRUNCATE
        $all_messages = $this->getMessages();
        $this->_logger->debug('Checking messages count [' . count($all_messages) . ']');
        $messages = $this->_truncate(
            $all_messages,
            $this->evaluateString($this->_properties['max_count']),
            $this->evaluateString($this->_properties['truncate_to'])
        );

        $truncated = Util::getTruncatedPart($all_messages, $messages);

        // TRUNCATED FLOW
        if (count($truncated)) {
            $this->_logger->debug('Got messages after truncation [' . print_r($messages, true) . ']. Executing truncated flow');
            $params         =  $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
            $var_name       = $this->evaluateString($this->_properties['result_var']) ?? null;
            if ($var_name) {
                $params->setServiceParam($this->evaluateString($this->_properties['result_var']), [
                    'messages' => $messages,
                    'truncated' => $truncated,
                ]);
            }

            foreach ($this->_truncatedFlow as $elem) {
                $elem->read($request, $response);
            }
        } else {
            $this->_logger->debug('No need tp truncate  messages count [' . count($messages) . ']');
        }

        // PASS MESSAGES TO THE UPPER LAYER
        /** @var \Convo\Gpt\IMessages $container */
        $container = $this->findAncestor('\Convo\Gpt\IMessages');

        foreach ($messages as $message) {
            $container->registerMessage($message);
        }
    }

    protected function _truncate($messages, $max, $to)
    {
        $this->_logger->debug('Going to check for truncation messages [' . count($messages) . '] against max: ' . $max . ' to: ' . $to);
        return Util::truncate($messages, $max, $to);
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[]';
    }
}
