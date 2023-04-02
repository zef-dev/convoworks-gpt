<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\GptApiFactory;
use Convo\Gpt\IChatAction;
use Convo\Gpt\IChatPrompt;
use Convo\Core\DataItemNotFoundException;
use Convo\Gpt\IChatApp;

class ChatAppElement extends AbstractWorkflowContainerComponent implements IChatApp, IConversationElement
{
    const PREFIX_BOT        =   'Bot:';
    const PREFIX_USER       =   'User:';
    const PREFIX_WEBSITE    =   'Website:';
    
    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    /**
     * @var IConversationElement[]
     */
    private $_ok = [];

    /**
     * @var IConversationElement[]
     */
    private $_actions = [];

    /**
     * @var IConversationElement[]
     */
    private $_prompts = [];

    /**
     * @var IChatAction[]
     */
    private $_chatActions = [];

    /**
     * @var IChatPrompt[]
     */
    private $_chatPrompts = [];
    
    
    /**
     * @var string
     */
    private $_lastPrompt;
    
    
    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);
        
        $this->_gptApiFactory  =	$gptApiFactory;
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
        
        foreach ( $properties['actions'] as $element) {
            $this->_actions[] = $element;
            $this->addChild($element);
        }
        
        foreach ( $properties['prompts'] as $element) {
            $this->_prompts[] = $element;
            $this->addChild($element);
        }
    }
    
    public function registerPrompt( $prompt)
    {
        $this->_chatPrompts[] = $prompt;
    }
    
    public function registerAction( $action)
    {
        $this->_chatActions[] = $action;
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        foreach ( $this->_actions as $action) {
            $action->read( $request, $response);
        }
        
        foreach ( $this->_prompts as $prompt) {
            $prompt->read( $request, $response);
        }
        
        $params         =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        
        $messages       =   $this->evaluateString( $this->_properties['messages']);
        $user_message   =   $this->evaluateString( $this->_properties['user_message']);
        $bot_response   =   $this->_getCompletion( $messages, trim( $user_message), self::PREFIX_USER);
        
        $this->_logger->debug( 'Got bot response ['.$bot_response.']');
        $bot_response   =   $this->_fixBotJsonResponse( $bot_response);
        $this->_logger->debug( 'Got bot response 2 ['.$bot_response.']');
        $json           =   json_decode( trim( $bot_response), true);
        $this->_logger->debug( 'Got bot response as data ['.print_r( $json, true).']');
        
        if ( $json !== false && isset( $json['action_id'])) 
        {
            $messages[]     =   self::PREFIX_BOT.trim( $bot_response);
            
            try 
            {
                $action         =   $this->_getAction( $json['action_id']);
                
                $params->setServiceParam( 'data', $json);
                
                $action_response = $action->executeAction( $json, $request, $response);
                $this->_logger->debug( 'Got action response ['.print_r( $action_response, true).']');
                
                $action_response    =   json_encode( $action_response);
                $bot_response       =   $this->_getCompletion( $messages, $action_response, self::PREFIX_WEBSITE);
            } 
            catch ( DataItemNotFoundException $e) 
            {
                $bot_response   =   $this->_getCompletion( $messages, json_encode( ['message'=>'Action ['.$json['action_id'].'] is not defined']), self::PREFIX_WEBSITE);
            }
        }
        
        $messages[]    =   self::PREFIX_BOT.' '.trim( $bot_response);

        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), [
            'messages' => $messages,
            'bot_response' => $bot_response,
            'last_prompt' => $this->_lastPrompt
        ]);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    public function getActions()
    {
        return $this->_chatActions;
    }
    
    private function _getAction( $actionId)
    {
        foreach ( $this->_chatActions as $action)
        {
            if ( $action->accepts( $actionId)) {
                return $action;
            }
        }
        throw new DataItemNotFoundException( 'Action ['.$actionId.'] not found');
    }
    
    private function _fixBotJsonResponse( $original)
    {
        $trimmed   =   stripslashes( $original);
        $trimmed   =   trim( $trimmed, '"n');
        return $trimmed;
    }
    
    private function _getCompletion( &$messages, $lastMessge, $lastMessagePrefix)
    {
        $api_key        =   $this->evaluateString( $this->_properties['api_key']);
        $api            =   $this->_gptApiFactory->getApi( $api_key);
        
        $messages[]     =   $lastMessagePrefix.' '.trim( $lastMessge);
        $conversation   =   implode( "\n", $messages);
        
        
        $prompt     =   $this->_getPrompt();
        $prompt     .=  "\n\n";
        $prompt     .=  $conversation;
        $prompt     .=  "\n";
        $prompt     .=  self::PREFIX_BOT;
        
        $this->_logger->debug( 'Got prompt ============');
        $this->_logger->debug( "\n".$prompt);
        $this->_logger->debug( '============');
        
        $http_response   =   $api->completion( [
            'model' => 'text-davinci-003',
            'prompt' => json_encode( $prompt),
            'temperature' => 0.7,
            'max_tokens' => 256,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stop' => [ self::PREFIX_USER, self::PREFIX_WEBSITE],
        ]);
        
        $this->_lastPrompt = $prompt;
        
        $bot_response  =    $http_response['choices'][0]['text'];
        return $bot_response;
    }
    
    
    
    private function _getPrompt()
    {
        $system_message =   $this->evaluateString( $this->_properties['system_message']);
//         $definitions    =   array_merge( $this->_chatPrompts, $this->_chatActions);
        
        $str    = $system_message;
//         $str .= "\n";
        foreach ( $this->_chatPrompts as $prompt) {
            $str .= "\n\n\n";
            $str .= $prompt->getPrompt();
        }
        
        return $str;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }

}
