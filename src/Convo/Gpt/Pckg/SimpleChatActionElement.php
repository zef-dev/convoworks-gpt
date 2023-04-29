<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\IChatAction;

class SimpleChatActionElement extends SimplePromptElement implements IChatAction
{

    private $_actionRequestDataVar;
    private $_actionResultData;
    
    /**
     * @var IConversationElement[]
     */
    private $_ok = [];
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_actionRequestDataVar    =   $properties['action_var'];
        $this->_actionResultData        =   $properties['result'];
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }
    
    // OVERRIDE PROMPT
    public function getActions()
    {
        return [ $this];
    }
    
   // ACTIONS INTERFACE
    public function executeAction( $data, IConvoRequest $request, IConvoResponse $response)
    {
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_actionRequestDataVar), $data);
        
        $this->_logger->debug( 'Executing action');
        
        foreach ( $this->_ok as $elem) {
            $elem->read( $request, $response);
        }
        
        $params        =    $this->getService()->getServiceParams( IServiceParamsScope::SCOPE_TYPE_REQUEST);
        return $this->evaluateString( $this->_actionResultData);
    }
    
    public function accepts( $actionId)
    {
        return $this->evaluateString( $this->_properties['action_id']) === $actionId;
    }
    
    public function getActionId()
    {
        return $this->_properties['action_id'];
    }
    
    public function autoActivate()
    {
        return $this->_properties['autoActivate'] ?? false;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_properties['action_id'].']';
    }



}
