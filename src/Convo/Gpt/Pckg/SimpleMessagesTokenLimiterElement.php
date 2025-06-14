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

class SimpleMessagesTokenLimiterElement extends AbstractWorkflowContainerComponent implements IConversationElement, IMessages
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

        // TRUNCATE BY TOKENS
        $all_messages = $this->getMessages();
        $max_tokens = intval($this->evaluateString($this->_properties['max_tokens']));
        $truncate_to_tokens = intval($this->evaluateString($this->_properties['truncate_to_tokens']));

        $this->_logger->debug('Checking messages token size [' . $this->_estimateMessagesTokens($all_messages) . ']');
        $messages = $this->_truncateByTokens($all_messages, $max_tokens, $truncate_to_tokens);

        $truncated = Util::getTruncatedPart($all_messages, $messages);

        // TRUNCATED FLOW
        if (count($truncated)) {
            $this->_logger->debug('Got messages after token truncation [' . print_r($messages, true) . ']. Executing truncated flow');
            $params         =  $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
            $var_name       = $this->evaluateString($this->_properties['result_var']) ?? null;
            if ($var_name) {
                $params->setServiceParam($var_name, [
                    'messages' => $messages,
                    'truncated' => $truncated,
                ]);
            }

            foreach ($this->_truncatedFlow as $elem) {
                $elem->read($request, $response);
            }
        } else {
            $this->_logger->debug('No need to truncate messages by tokens [' . $this->_estimateMessagesTokens($messages) . ']');
        }

        // PASS MESSAGES TO THE UPPER LAYER
        /** @var \Convo\Gpt\IMessages $container */
        $container = $this->findAncestor('\Convo\Gpt\IMessages');

        foreach ($messages as $message) {
            $container->registerMessage($message);
        }
    }

    protected function _truncateByTokens($messages, $max_tokens, $truncate_to_tokens)
    {
        $this->_logger->debug('Going to check for truncation by tokens [' . $this->_estimateMessagesTokens($messages) . '] against max: ' . $max_tokens . ' to: ' . $truncate_to_tokens);

        // Remove oldest messages until under max_tokens
        $trimmed = $messages;
        while (count($trimmed) > 0 && $this->_estimateMessagesTokens($trimmed) > $max_tokens) {
            array_shift($trimmed);
        }

        // Further trim to truncate_to_tokens if needed
        while (count($trimmed) > 0 && $this->_estimateMessagesTokens($trimmed) > $truncate_to_tokens) {
            array_shift($trimmed);
        }

        return $trimmed;
    }

    protected function _estimateMessagesTokens($messages)
    {
        $total = 0;
        foreach ($messages as $msg) {
            if (isset($msg['content'])) {
                $total += \Convo\Gpt\Pckg\GptPackageDefinition::estimateTokens($msg['content']);
            }
        }
        return $total;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[]';
    }
}
