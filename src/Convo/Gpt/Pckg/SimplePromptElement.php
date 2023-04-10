<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Gpt\IChatPrompt;
use Convo\Gpt\IChatPromptContainer;

class SimplePromptElement extends AbstractWorkflowContainerComponent implements IChatPrompt, IConversationElement
{
    private $_title;
    private $_content;
    
    /**
     * @var IChatPromptContainer
     */
    private $_promptContainer;
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_title = $properties['title'];
        $this->_content = $properties['content'];
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $this->_promptContainer = $this->findAncestor( '\Convo\Gpt\IChatPromptContainer');
        $this->_promptContainer->registerPrompt( $this);
    }
        
    public function getPromptContent()
    {
        $title      =   $this->_titleToHeading( $this->evaluateString( $this->_properties['title']));
        $content    =   $this->evaluateString( $this->_properties['content']);
        return $title."\n\n".$content;
    }
    
    protected function _titleToHeading( $title)
    {
        $hashes     =   str_repeat( '#', $this->getDepth());
        $title      =   $hashes ? $hashes.' '.$title : $title;
        return $title;
    }
    
    public function getDepth()
    {
        return $this->_promptContainer->getDepth() + 1;
    }
    
    public function getActions()
    {
        return [];
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }

}
