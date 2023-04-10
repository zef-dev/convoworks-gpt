<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Gpt\IChatPrompt;

class PromptSectionElement extends SimplePromptElement
{
    

    /**
     * @var IConversationElement[]
     */
    private $_prompts = [];
    
    /**
     * @var IChatPrompt[]
     */
    private $_chatPrompts = [];
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        foreach ( $properties['prompts'] as $element) {
            $this->_prompts[] = $element;
            $this->addChild($element);
        }
    }
    
    public function getPrompt()
    {
        $str =  parent::getPrompt();
        
        foreach ( $this->_chatPrompts as $prompt) {
            $str .= "\n\n\n";
            $str .= $prompt->getPrompt();
        }
        
        return $str;
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }






}
