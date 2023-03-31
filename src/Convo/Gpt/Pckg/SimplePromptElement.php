<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IChatPrompt;

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
//         foreach ( $this->_childPrompts as $elem) {
//             $elem->read( $request, $response);
//         }
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
