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

class ChatAppElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    
    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    /**
     * @var IConversationElement[]
     */
    private $_ok = [];

    /**
     * @var IChatAction[]
     */
    private $_actions = [];
    
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
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        $api            =   $this->_gptApiFactory->getApi( $api_key);
        
        $messages       =   $this->evaluateString( $this->_properties['messages']);
        $user_message   =   $this->evaluateString( $this->_properties['user_message']);
        $messages[]     =   'User: '.trim( $user_message);
        $conversation   =   implode( "\n", $messages);
        
        
        $prompt     =   $this->_getPrompt();
        $prompt     .=  "\n\n";
        $prompt     .=  "---------------------------------";
        $prompt     .=  "\n\n";
        $prompt     .=  $conversation;
        $prompt     .=  "\n";
        $prompt     .=  'Bot: ';
        
        $this->_logger->debug( 'Got prompt ['.$prompt.']');
        
        $http_response   =   $api->completion( [
            'model' => 'text-davinci-003',
            'prompt' => json_encode( $prompt),
            'temperature' => 0.7,
            'max_tokens' => 256,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stop' => ['User:','Website:'],
        ]);

        $bot_response  =    $http_response['choices'][0]['text'];
        
        $messages[]    =   'Bot: '.trim( $bot_response);
        
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), [
            'messages' => $messages,
            'bot_response' => $bot_response,
        ]);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    private function _getPrompt()
    {
        $system_message =   $this->evaluateString( $this->_properties['system_message']);
        $definitions    =   $this->_getPromptDefinitions( $this->_actions);
        
        $str = $system_message;
        $str .= "\n\n";
        foreach ( $definitions as $prompt) {
            $str .= "\n";
            $str .= $prompt->getPrompt();
        }
        
        return $str;
    }
    

    /**
     * @param IChatAction[] $actions
     * @return IChatPrompt[]
     */
    private function _getPromptDefinitions( $actions)
    {
        $prompts = [];
        
        foreach ( $actions as $action) {
            $prompts[] = $action;
        }
        return $prompts;
    }
    

    private function _getConversation()
    {
        $str             =   '';
        $user_message    =   $this->evaluateString( $this->_properties['user_message']);
        $str            .=   "\n";
        $str            .=   "User: ".$user_message;
        return $str;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
