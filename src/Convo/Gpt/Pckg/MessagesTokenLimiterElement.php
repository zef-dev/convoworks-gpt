<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Gpt\GptApiFactory;
use Convo\Gpt\Util;

class MessagesTokenLimiterElement extends SimpleMessagesTokenLimiterElement
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

    protected function _truncateByTokens($messages, $max_tokens, $truncate_to_tokens)
    {
        $this->_logger->debug('Truncating messages by tokens [' . $this->_estimateMessagesTokens($messages) . '] to [' . $truncate_to_tokens . '] max [' . $max_tokens . ']');
        $tokens = $this->_estimateMessagesTokens($messages);
        if ($tokens < $max_tokens) {
            return $messages;
        }

        // Remove oldest messages until under max_tokens
        $trimmed = $messages;
        while (count($trimmed) > 0 && $this->_estimateMessagesTokens($trimmed) > $max_tokens) {
            array_shift($trimmed);
        }

        // Further trim to truncate_to_tokens if needed
        $truncated = [];
        while (count($trimmed) > 0 && $this->_estimateMessagesTokens($trimmed) > $truncate_to_tokens) {
            $truncated[] = array_shift($trimmed);
        }

        $this->_logger->debug('Truncated messages [' . print_r($truncated, true) . ']');

        if (count($truncated)) {
            // SUMMARIZE
            $summarized = $this->_summarize($truncated);
            $trimmed = array_merge([['role' => 'system', 'content' => $summarized]], $trimmed);
        }

        return $trimmed;
    }

    private function _summarize($conversation)
    {
        $messages   = [[
            'role' => 'system',
            'content' => $this->evaluateString($this->_properties['system_message'])
        ]];

        $messages[] = [
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
