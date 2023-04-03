<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Gpt\IChatPrompt;

class SimplePromptElement extends AbstractWorkflowContainerComponent implements IChatPrompt, IConversationElement
{
    

    private $_title;
    private $_content;
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_title = $properties['title'];
        $this->_content = $properties['content'];
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $app = $this->findAncestor( '\Convo\Gpt\IChatApp');
        /* @var \Convo\Gpt\IChatApp $app */
        
        $app->registerPrompt( $this);
    }
        
    public function getPrompt()
    {
        $title      =   $this->evaluateString( $this->_properties['title']);
        $content    =   $this->evaluateString( $this->_properties['content']);
        return $title."\n\n".$content;
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }






}
