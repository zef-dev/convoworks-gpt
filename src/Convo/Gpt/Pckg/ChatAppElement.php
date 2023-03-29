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
        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        $api            =   $this->_gptApiFactory->getApi( $api_key);
        
        $prompt     =   $this->_getPrompt();
        $prompt     .=  "\n\n";
        $prompt     .=  "---------------------------------";
        $prompt     .=  "\n\n";
        $prompt     .=  $this->_getConversation();
        
        $http_response   =   $api->completion( [
            'model' => $this->evaluateString( $this->_properties['model']),
            'prompt' => json_encode( $prompt),
            'temperature' => (float)$this->evaluateString( $this->_properties['temperature']),
            'max_tokens' => (int)$this->evaluateString( $this->_properties['max_tokens']),
            'top_p' => (float)$this->evaluateString( $this->_properties['top_p']),
            'frequency_penalty' => (float)$this->evaluateString( $this->_properties['frequency_penalty']),
            'presence_penalty' => (float)$this->evaluateString( $this->_properties['presence_penalty']),
        ]);
        
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), $http_response);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    private function _getPrompt()
    {
//         $system_message =   $this->evaluateString( $this->_properties['system_message']);
        $actions        =   $this->_getWebsiteActions();
        $definitions    =   $this->_getPromptDefinitions( $actions);
        
        $str = '';
        foreach ( $definitions as $prompt) {
            $str .= $prompt->getPrompt();
        }
        
        return $str;
    }
    

    /**
     * @param IChatAction[] $actions
     * @return IChatPrompt
     */
    private function _getPromptDefinitions( $actions)
    {}
    
    /**
     * @return IChatAction[]
     */
    private function _getWebsiteActions()
    {
        return [];
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
