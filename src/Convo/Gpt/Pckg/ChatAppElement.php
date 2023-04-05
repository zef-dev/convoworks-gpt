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
    const PREFIX_BOT        =   'Bot: ';
    const PREFIX_USER       =   'User: ';
    const PREFIX_WEBSITE    =   'Website: ';
    
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
    
    public function getActions()
    {
        return $this->_chatActions;
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        foreach ( $this->_actions as $action) {
            $action->read( $request, $response);
        }
        
        foreach ( $this->_prompts as $prompt) {
            $prompt->read( $request, $response);
        }
        
        $messages       =   $this->evaluateString( $this->_properties['messages']);
        $user_message   =   $this->evaluateString( $this->_properties['user_message']);
        
        $bot_response   =   $this->_getCompletion( $messages, trim( $user_message), self::PREFIX_USER);
        $bot_response   =   $this->_handleBotResponse( $bot_response, $messages, $request, $response);
        
        $messages[]     =   self::PREFIX_BOT.trim( $bot_response);

        $params         =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), [
            'messages' => $messages,
            'bot_response' => $bot_response,
            'last_prompt' => $this->_lastPrompt
        ]);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    private function _handleBotResponse( $botResponse, &$messages, IConvoRequest $request, IConvoResponse $response)
    {
        if ( $this->_isActionCandidate( $botResponse))
        {
            $corrected_response   =   $this->_fixBotJsonResponse( $botResponse);
            try
            {
                $json           =   $this->_parseActionJson( $corrected_response);
                
                $this->_logger->debug( 'Got bot response as data ['.print_r( $json, true).']');
                
                $messages[]     =   self::PREFIX_BOT.trim( $corrected_response);
                $botResponse    =   $this->_executeAction( $json, $messages, $request, $response);
                $botResponse    =   $this->_handleBotResponse( $botResponse, $messages, $request, $response);
            }
            catch ( \InvalidArgumentException $e)
            {
                // NO JSON FOUND
                $this->_logger->warning( $e->getMessage());
            }
        }
        return $botResponse;
    }
    
    
    private function _getPrompt()
    {
        $str    = $this->evaluateString( $this->_properties['system_message']);
        
        foreach ( $this->_chatPrompts as $prompt) {
            $str .= "\n\n\n";
            $str .= $prompt->getPrompt();
        }
        
        return $str;
    }
    
    private function _executeAction( $json, &$messages, IConvoRequest $request, IConvoResponse $response)
    {
        $this->_logger->info( 'Executing action ['.$json['action_id'].']');
        
        try
        {
            $action             =   $this->_getAction( $json['action_id']);
            $action_response    =   $action->executeAction( $json, $request, $response);
            $action_response    =   json_encode( $action_response);
            
            $this->_logger->debug( 'Got action response ['.$action_response.']');
        }
        catch ( DataItemNotFoundException $e)
        {
            $action_response    =   json_encode( ['message'=>'Action ['.$json['action_id'].'] is not defined']);
        }
        
        return $this->_getCompletion( $messages, $action_response, self::PREFIX_WEBSITE);;
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
        $trimmed   =   trim( $trimmed, '"');
        
        if ( $original !== $trimmed) {
            $this->_logger->warning( 'JSON information found, but had to be corrected ['.$original.']['.$trimmed.']');
        }
        
        return $trimmed;
    }
    
    private function _isActionCandidate( $message)
    {
        if ( strpos( $message, 'action_id') !== false) {
            return true;
        }
        return false;
    }
    
    private function _parseActionJson( $message)
    {
        $json           =   json_decode( trim( $message), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException( 'No valid JSON found in message ['.$message.']');
        }
        if ( !isset( $json['action_id']) || empty( $json['action_id'])) {
            throw new \InvalidArgumentException( 'No action_id in JSON found in message ['.$message.']');
        }
        return $json;
    }
    
    private function _getCompletion( &$messages, $lastMessge, $lastMessagePrefix)
    {
        $api_key        =   $this->evaluateString( $this->_properties['api_key']);
        $api            =   $this->_gptApiFactory->getApi( $api_key);
        
        $messages[]     =   $lastMessagePrefix.trim( $lastMessge);
        $conversation   =   implode( "\n", $messages);
        
        
        $prompt     =   $this->_getPrompt();
        $prompt     .=  "\n\n";
        $prompt     .=  $conversation;
        $prompt     .=  "\n";
        $prompt     .=  self::PREFIX_BOT;
        
        $this->_logger->debug( 'Got prompt ============');
        $this->_logger->debug( "\n".$prompt);
        $this->_logger->debug( '============');
        
        $http_response   =   $api->completion( $this->_getApiOptions( json_encode( $prompt)));
        
        $this->_lastPrompt = $prompt;
        
        $bot_response  =    $http_response['choices'][0]['text'];
        return $bot_response;
    }
    
    private function _getApiOptions( $prompt)
    {
        $options = $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);
        $options['prompt'] = $prompt;
        return $options;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }

}
