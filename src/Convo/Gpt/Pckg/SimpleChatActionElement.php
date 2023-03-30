<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IChatAction;
use Convo\Core\Params\IServiceParamsScope;

class SimpleChatActionElement extends AbstractWorkflowContainerComponent implements IChatAction
{
    

    private $_title;
    private $_content;
    
    private $_actionRequestDataVar  =   'action';
    private $_actionResultData      =   '${action_result}';
    
    /**
     * @var IConversationElement[]
     */
    private $_ok = [];
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_title = $properties['title'];
        $this->_content = $properties['content'];
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $this->_logger->debug( 'Executing action ['.$this->getActionId().']');
        foreach ( $this->_ok as $elem) {
            $elem->read( $request, $response);
        }
    }
    
    public function getPrompt()
    {
        $title      =   $this->evaluateString( $this->_properties['title']);
        $content    =   $this->evaluateString( $this->_properties['content']);
        return $title."\n".$content;
    }
    
    public function executeAction( $data, IConvoRequest $request, IConvoResponse $response)
    {
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->_actionRequestDataVar, $data);
        
        $this->read( $request, $response);
        
        $params        =    $this->getService()->getServiceParams( IServiceParamsScope::SCOPE_TYPE_REQUEST);
        
        return $this->evaluateString( $this->_actionResultData);
    }
    
    public function getActionId()
    {
        return $this->evaluateString( $this->_properties['action_id']);
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }






}
