<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IChatAction;

class SimpleChatActionElement extends AbstractWorkflowContainerComponent implements IChatAction
{
    

    private $_title;
    private $_content;
    
    
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
    }
    
    public function getPrompt()
    {
        $title      =   $this->evaluateString( $this->_properties['title']);
        $content    =   $this->evaluateString( $this->_properties['content']);
        return $title."\n".$content;
    }
    
    public function executeAction( $data)
    {
        return ['message' => 'OK'];
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
