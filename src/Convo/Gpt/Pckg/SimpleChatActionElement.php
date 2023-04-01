<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IChatAction;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\DataItemNotFoundException;

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
        $app = $this->findAncestor( '\Convo\Gpt\IChatApp');
        /* @var \Convo\Gpt\IChatApp $app */
        
        $app->registerAction( $this);
    }
    
    public function findAncestor( $class)
    {
        $parent = $this;
        while ( $parent = $parent->getParent()) {
            if ( is_a( $parent, $class)) {
                return $parent;
            }
            
            if ( $parent === $this->getService()) {
                break;
            }
        }
        
        throw new DataItemNotFoundException( 'Ancestro with class ['.$class.'] not found');
    }
    
    
    
    public function getPrompt()
    {
        $title      =   $this->evaluateString( $this->_properties['title']);
        $content    =   $this->evaluateString( $this->_properties['content']);
        return $title."\n\n".$content;
    }
    
    public function executeAction( $data, IConvoRequest $request, IConvoResponse $response)
    {
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->_actionRequestDataVar, $data);
        
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
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }

}
