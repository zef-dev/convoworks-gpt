<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Gpt\GptApiFactory;
use Convo\Gpt\Util;

class MessagesLimiterElement extends SimpleMessagesLimiterElement
{
    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    public function __construct($properties, $gptApiFactory)
    {
        parent::__construct($properties);

        $this->_gptApiFactory  =    $gptApiFactory;
    }

    protected function _truncateMessages($allMessages)
    {
        $messages = parent::_truncateMessages($allMessages);
        $truncated = Util::getTruncatedPart($allMessages, $messages);

        if ($truncated) {
            $this->_logger->debug('Truncated messages [' . print_r($truncated, true) . ']');
            $summarized = $this->_sumarize($truncated);
            $messages = array_merge([['role' => 'system', 'content' => $summarized]], $messages);
        }
        return $messages;
    }

    private function _sumarize($conversation)
    {
        $messages   = [[
            'role' => 'system',
            'content' => $this->evaluateString($this->_properties['system_message'])
        ]];

        $messages[] =   [
            'role' => 'user',
            'content' => Util::serializeMessages($conversation, true)
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
