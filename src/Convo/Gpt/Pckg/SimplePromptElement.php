<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IChatPrompt;
use Convo\Core\DataItemNotFoundException;

class SimplePromptElement extends AbstractWorkflowContainerComponent implements IChatPrompt
{
    

    private $_title;
    private $_content;
    
    /**
     * @var IChatPrompt[]
     */
    private $_childPrompts = [];
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_title = $properties['title'];
        $this->_content = $properties['content'];
        
        foreach ( $properties['childPrompts'] as $element) {
            $this->_childPrompts[] = $element;
            $this->addChild($element);
        }
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $app = $this->findAncestor( '\Convo\Gpt\IChatApp');
        /* @var \Convo\Gpt\IChatApp $app */
        
        $app->registerPrompt( $this);
        
//         foreach ( $this->_childPrompts as $elem) {
//             $elem->read( $request, $response);
//         }
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
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }






}
