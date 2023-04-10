<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Gpt\IChatPrompt;
use Convo\Gpt\IChatPromptContainer;

class PromptSectionElement extends SimplePromptElement implements IChatPromptContainer
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
    
    
    public function getPromptContent()
    {
        $str =  parent::getPromptContent();
        
        foreach ( $this->_chatPrompts as $prompt) {
            $str .= "\n\n\n";
            $str .= $prompt->getPromptContent();
        }
        
        return $str;
    }
    
    public function getPrompts()
    {
        return $this->_chatPrompts;
    }

    public function getActions()
    {
        $actions = [];
        
        foreach ( $this->_chatPrompts as $prompt) {
            $actions = array_merge( $actions, $prompt->getActions());
        }
        return $actions;
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Gpt\IChatPromptContainer::registerPrompt()
     */
    public function registerPrompt( $prompt)
    {
        $this->_chatPrompts[] = $prompt;
    }


    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }
    
}
