<?php declare(strict_types=1);

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
    
    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);
        
        $this->_gptApiFactory  =	$gptApiFactory;
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $messages    =   $this->evaluateString( $this->_properties['messages']);
        
        $serialized  =   $this->_serializeConversation( $messages);
        $this->_logger->debug( 'Got serialized conversation ['.$serialized.']');
        
        $questions   =   $this->_generateQuestions( $serialized);
        
        $this->_logger->debug( 'Got generated questions ['.print_r( $questions, true).']');
        
        $params      =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), $questions);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    private function _serializeConversation( $messages)
    {
        $conversation = [];
        foreach ( $messages as $message) {
            if ( $message['role'] === 'system') {
                continue;
            }
            if ( $message['role'] === 'function') {
                continue;
            }
            if ( isset( $message['function_call'])) {
                continue;
            }
            $conversation[] = $message['role'].': '.$message['content'];
        }
        
        $max_count      =   intval( $this->evaluateString( $this->_properties['messages_count']));
        if ( $max_count) {
            $this->_logger->debug( 'Trimming conversation to last ['.$max_count.'] messages');
            $conversation   =   $this->_getLastItems( $conversation, $max_count);
        }
        return implode( "\n\n", $conversation);
    }
    
    private function _getLastItems( $array, $n) {
        $reversed_array = array_reverse($array);
        $last_n_items = array_slice( $reversed_array, 0, $n);
        return array_reverse( $last_n_items);
    }
    
    private function _parseQuestions( $responseMessage)
    {
        if ( empty( $responseMessage) || $responseMessage === 'NA') {
            return [];
        }
        
        $lines = explode("\n", $responseMessage);
        
        $questions = [];
        
        foreach ( $lines as $line) {
            if ( strpos( $line, ":") !== false) {
                $question = trim(substr( $line, strpos( $line, ":") + 1));
                if (!empty($question)) {
                    $questions[] = $question;
                }
            }
        }
        return $questions;
    }
    
    private function _generateQuestions( $serialized) 
    {
        $system     =   $this->evaluateString( $this->_properties['system_message']);
        
        $messages   =   [
            [ 'role' => 'system', 'content' => $system],
            [ 'role' => 'user', 'content' => $serialized],
        ];
        
        $api_key        =   $this->evaluateString( $this->_properties['api_key']);
        $api            =   $this->_gptApiFactory->getApi( $api_key);
        
        $http_response  =   $api->chatCompletion( $this->_getApiOptions( $messages));
        
        return $this->_parseQuestions( $http_response['choices'][0]['message']['content']);
    }
    
    
    private function _getApiOptions( $messages)
    {
        $options = $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);
        $options['messages'] = $messages;
        return $options;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
