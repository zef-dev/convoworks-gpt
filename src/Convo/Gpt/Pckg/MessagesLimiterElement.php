<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\GptApiFactory;
use Convo\Gpt\IMessages;
use Convo\Gpt\Util;

class MessagesLimiterElement extends AbstractWorkflowContainerComponent implements IConversationElement, IMessages
{

    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;



    private $_messages = [];


    /**
     * @var IConversationElement[]
     */
    private $_messagesDefinition = [];

    public function __construct($properties, $gptApiFactory)
    {
        parent::__construct($properties);

        $this->_gptApiFactory  =    $gptApiFactory;

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

    private function _truncate($messages, $max, $to)
    {
        $this->_logger->debug('Truncating messages [' . count($messages) . '] to [' . $to . '] max [' . $max . ']');
        $count = count($messages);
        if ($count < $max) {
            return $messages;
        }

        // TRUNCATE
        $new_messages = Util::truncate($messages, $max, $to);
        $truncated = Util::getTruncatedPart($messages, $new_messages);

        $this->_logger->debug('Truncated messages [' . print_r($truncated, true) . ']');

        // SUMMARIZE
        $summarized = $this->_sumarize($truncated);
        $new_messages = array_merge([['role' => 'system', 'content' => $summarized]], $new_messages);

        return $new_messages;
    }

    private function _sumarize($conversation)
    {
        $messages   = [[
            'role' => 'system',
            'content' => $this->evaluateString($this->_properties['system_message'])
        ]];

        $messages[] =   [
            'role' => 'user',
            'content' => Util::serializeMessages($conversation)
        ];

        $api_key    =   $this->evaluateString($this->_properties['api_key']);
        $base_url   =   $this->evaluateString($this->_properties['base_url'] ?? null);

        $api        =   $this->_gptApiFactory->getApi($api_key, $base_url);

        $http_response   =   $api->chatCompletion($this->_buildApiOptions($messages));

        return $http_response['choices'][0]['message']['content'];
    }

    private function _buildApiOptions($messages)
    {
        $options = $this->getService()->evaluateArgs($this->_properties['apiOptions'], $this);

        $options['messages'] = $messages;

        return $options;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[]';
    }
}
