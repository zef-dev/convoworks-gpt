<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\GptApiFactory;
use Convo\Gpt\IChatPrompt;
use Convo\Core\DataItemNotFoundException;
use Convo\Gpt\IChatPromptContainer;

abstract class AbstractChatAppElement extends AbstractWorkflowContainerComponent implements IChatPromptContainer, IConversationElement
{
    /**
     * @var GptApiFactory
     */
    protected $_gptApiFactory;

    /**
     * @var IConversationElement[]
     */
    protected $_prompts = [];

    /**
     * @var IChatPrompt[]
     */
    protected $_chatPrompts = [];
    
    
    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);
        
        $this->_gptApiFactory  =	$gptApiFactory;
        
        foreach ( $properties['prompts'] as $element) {
            $this->_prompts[] = $element;
            $this->addChild($element);
        }
    }
    
    // ELEMENT
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        foreach ( $this->_prompts as $prompt) {
            $prompt->read( $request, $response);
        }
    }
    
    // PROMPTS CONTAINER
    public function getDepth()
    {
        return 1;
    }
    
    public function getPrompts()
    {
        return $this->_chatPrompts;
    }
    
    /**
     * {@inheritDoc}
     * @see \Convo\Gpt\IChatPromptContainer::getActions()
     */
    public function getActions()
    {
        $actions = [];
        
        foreach ( $this->_chatPrompts as $prompt) {
            $actions = array_merge( $actions, $prompt->getActions());
        }
        return $actions;
    }
    
    public function registerPrompt( $prompt)
    {
        $this->_chatPrompts[] = $prompt;
    }
    
    public function getPromptContent()
    {
        $str    = $this->evaluateString( $this->_properties['system_message']);
        
        foreach ( $this->_chatPrompts as $prompt) {
            $str .= "\n\n\n";
            $str .= $prompt->getPromptContent();
        }
        
        return $str;
    }

    
    // ACTIONS
    
    /**
     * @param string $actionId
     * @throws DataItemNotFoundException
     * @return \Convo\Gpt\IChatAction
     */
    protected function _getActionById( $actionId)
    {
        foreach ( $this->getActions() as $action)
        {
            if ( $action->accepts( $actionId)) {
                return $action;
            }
        }
        throw new DataItemNotFoundException( 'Action ['.$actionId.'] not found');
    }
    
    protected function _isActionCandidate( $message)
    {
        if ( strpos( $message, 'action_id') !== false) {
            return true;
        }
        if ( strpos( $message, '{') !== false) {
            return true;
        }
        if ( strpos( $message, '}') !== false) {
            return true;
        }
        return false;
    }
    
    protected function _parseActionJson( $message)
    {
        $json       =   json_decode( trim( $message), true);
        
        if ( JSON_ERROR_NONE !== json_last_error()) {
            $json       =   $this->_parseActionJsonWithGpt( $message);
        }
        
        if ( !isset( $json['action_id']) || empty( $json['action_id'])) {
            throw new \InvalidArgumentException( 'No action_id in JSON found in message ['.$message.']');
        }
        
        return $json;
    }
    
    protected function _parseActionJsonWithGpt( $message)
    {
        $messages   =   [];
        $message    =   'Extract just JSON information from the following message. In case of multiple JSONs, write just the first one.

'.$message;
        
        $messages[] =   [
            'role' => 'user',
            'content' => $message
        ];
        
        
        $this->_logger->debug( 'Parsing action message ============');
        $this->_logger->debug( "\n".$message);
        $this->_logger->debug( '============');
        
        $http_response  =   $this->_getGptApi()->chatCompletion( [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 256,
            'messages' => $messages,
        ]);
        $bot_response   =   $http_response['choices'][0]['message']['content'];
        
        $json           =   json_decode( trim( $bot_response), true);
        
        if ( JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException( 'No valid JSON found in message ['.$message.']');
        }
        
        return $json;
    }
    
    // API
    /**
     * @return \Convo\Gpt\GptApi
     */
    protected function _getGptApi()
    {
        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        return $this->_gptApiFactory->getApi( $api_key);
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }
}
