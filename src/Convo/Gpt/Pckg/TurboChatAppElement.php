<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\DataItemNotFoundException;
use Convo\Gpt\ValidationException;

class TurboChatAppElement extends AbstractChatAppElement
{

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
        
        $bot_response   =   $this->_getCompletion( $messages);
        $bot_response   =   $this->_handleBotResponse( $bot_response, $messages, $request, $response);
        
        $messages[]     =   $bot_response;

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
        if ( $this->_isActionCandidate( $botResponse['content']))
        {
            $this->_logger->debug( 'We have action candidate');
            
            try
            {
                $json           =   $this->_parseActionJson( $botResponse['content']);
                
                $this->_logger->debug( 'Got bot response as data ['.print_r( $json, true).']');
                
                $messages[]     =   [
                    'role' => 'assistant',
                    'content' => json_encode( $json)
                ];
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
        
        $messages[] = [
            'role' => 'system',
            'content' => $action_response
        ];
        
        return $this->_getCompletion( $messages);
    }
    
    // API
    private function _getCompletion( $messages)
    {
        $prompt             =   $this->getPromptContent();
        $this->_lastPrompt  =   $prompt;
        
        $options                =   $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);
        $options['messages']    =   array_merge(
            [[
                'role' => 'system',
                'content' => $prompt
            ]],
            $messages);
        
        $response_data      =   $this->_getGptApi()->chatCompletion( $options);

        $message            =   $response_data['choices'][0]['message'];
        $message['content'] =   trim( $message['content'], '`');
        $message['content'] =   trim( $message['content'], '\n');

        return $message;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }




}
