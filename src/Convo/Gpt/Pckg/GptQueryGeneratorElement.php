<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\GptApiFactory;

class GptQueryGeneratorElement extends AbstractWorkflowContainerComponent implements IConversationElement
{

    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    /**
     * @var IConversationElement[]
     */
    private $_ok = [];

    public function __construct($properties, $gptApiFactory)
    {
        parent::__construct($properties);

        $this->_gptApiFactory  =    $gptApiFactory;

        foreach ($properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $messages    =   $this->evaluateString($this->_properties['messages']);

        $serialized  =   $this->_serializeConversation($messages);
        $this->_logger->debug('Got serialized conversation [' . $serialized . ']');

        $questions   =   $this->_generateQuestions($serialized);

        $this->_logger->info('Got generated questions [' . print_r($questions, true) . ']');

        $params      =   $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam($this->evaluateString($this->_properties['result_var']), $questions);

        foreach ($this->_ok as $elem) {
            $elem->read($request, $response);
        }
    }

    private function _serializeConversation($messages)
    {
        $conversation = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                continue;
            }
            if ($message['role'] === 'tool') {
                continue;
            }
            if (isset($message['tool_calls'])) {
                continue;
            }
            $conversation[] = ucfirst($message['role']) . ': ' . $message['content'];
        }

        $max_count      =   intval($this->evaluateString($this->_properties['messages_count']));
        if ($max_count) {
            $this->_logger->info('Trimming conversation to last [' . $max_count . '] messages');
            $conversation   =   $this->_getLastItems($conversation, $max_count);
        }
        return implode("\n\n", $conversation);
    }

    private function _getLastItems($array, $n)
    {
        $reversed_array = array_reverse($array);
        $last_n_items = array_slice($reversed_array, 0, $n);
        return array_reverse($last_n_items);
    }

    private function _parseQuestions($responseMessage)
    {
        if (empty($responseMessage) || $responseMessage === 'NA') {
            return [];
        }

        $lines = explode("\n", $responseMessage);

        $questions = [];

        foreach ($lines as $line) {
            if (strpos($line, ":") === 1 || strpos($line, ".") === 1) {
                $question = trim(substr($line, 2));
                if (!empty($question)) {
                    $questions[] = $question;
                }
            }
        }
        return $questions;
    }

    private function _generateQuestions($serialized)
    {
        $embeded = false;
        if (strpos($this->_properties['system_message'], '${conversation}') !== false) {
            $embeded = true;
        }

        $serialized =   '**Conversation to generate questions from**

' . $serialized;

        if ($embeded) {
            $system     =   $this->evaluateString($this->_properties['system_message'], ['conversation' => $serialized]);
            $messages   =   [
                ['role' => 'system', 'content' => $system],
            ];
        } else {
            $system     =   $this->evaluateString($this->_properties['system_message']);
            $messages   =   [
                ['role' => 'system', 'content' => $system],
                ['role' => 'system', 'content' => $serialized],
            ];
        }



        $api_key        =   $this->evaluateString($this->_properties['api_key']);
        $base_url       =   $this->evaluateString($this->_properties['base_url'] ?? null);
        $api            =   $this->_gptApiFactory->getApi($api_key, $base_url);

        $http_response  =   $api->chatCompletion($this->_buildApiOptions($messages));

        return $this->_parseQuestions($http_response['choices'][0]['message']['content']);
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
