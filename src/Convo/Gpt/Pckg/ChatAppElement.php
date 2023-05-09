<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\DataItemNotFoundException;
use Convo\Gpt\ValidationException;

class ChatAppElement extends AbstractChatAppElement
{
    const PREFIX_BOT        =   'Bot: ';
    const PREFIX_USER       =   'User: ';
    const PREFIX_WEBSITE    =   'Website: ';

    /**
     * @var IConversationElement[]
     */
    private $_ok = [];

    /**
     * @var string
     */
    private $_lastPrompt;
    
    
    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties, $gptApiFactory);
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }
    
    // ELEMENT
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        parent::read( $request, $response);
        
        $messages       =   $this->evaluateString( $this->_properties['messages']);
        $user_message   =   $this->evaluateString( $this->_properties['user_message']);
        
        $bot_response   =   $this->_getCompletion( $messages, trim( $user_message), self::PREFIX_USER, $request, $response);
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
            $this->_logger->debug( 'We have action candidate');
            
            try
            {
                $json           =   $this->_parseActionJson( $botResponse);
                
                $this->_logger->debug( 'Got bot response as data ['.print_r( $json, true).']');
                
                $messages[]     =   self::PREFIX_BOT.json_encode( $json);
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
    
    // ACTION HANDLING
    private function _executeAction( $json, &$messages, IConvoRequest $request, IConvoResponse $response)
    {
        $this->_logger->info( 'Executing action ['.$json['action_id'].']');
        
        try
        {
            $action             =   $this->_getActionById( $json['action_id']);
            $action_response    =   $action->executeAction( $json, $request, $response);
            $action_response    =   json_encode( $action_response);
            
            $this->_logger->debug( 'Got action response ['.$action_response.']');
        }
        catch ( DataItemNotFoundException $e)
        {
            $action_response    =   json_encode( ['message'=>'Action ['.$json['action_id'].'] is not defined']);
        }
        catch ( ValidationException $e)
        {
            $action_response    =   json_encode( ['message'=> $e->getMessage()]);
        }
        
        return $this->_getCompletion( $messages, $action_response, self::PREFIX_WEBSITE, $request, $response);
    }
    
    
    // API
    private function _getCompletion( &$messages, $lastMessge, $lastMessagePrefix, $request, $response)
    {
        $auto       =   [];
        
        $actions    =   $this->getActions();
        
        foreach ( $actions as $action)
        {
            if ( !$action->autoActivate()) {
                continue;
            }
            $auto[]     =   self::PREFIX_BOT.json_encode( ['action_id' => $action->getActionId()]);
            $auto[]     =   self::PREFIX_WEBSITE.json_encode( $action->executeAction( [], $request, $response));
        }
        
        $messages[]     =   $lastMessagePrefix.trim( $lastMessge);
        $conversation   =   implode( "\n", array_merge( $auto, $messages));
        
        $prompt         =   $this->getPromptContent();
        $prompt         .=  "\n\n";
        $prompt         .=  $conversation;
        $prompt         .=  "\n";
        $prompt         .=  self::PREFIX_BOT;
        
        $this->_logger->debug( 'Got prompt ============');
        $this->_logger->debug( "\n".$prompt);
        $this->_logger->debug( '============');
        
        $options            =   $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);
        $options['prompt']  =   json_encode( $prompt);
        
        $http_response      =   $this->_getGptApi()->completion( $options);
        
        $this->_lastPrompt  =   $prompt;
        
        $message            =   trim( $http_response['choices'][0]['text'], '`');
        $message            =   trim( $message, '\n');
        return $message;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }




}
