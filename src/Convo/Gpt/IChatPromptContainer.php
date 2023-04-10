<?php declare(strict_types=1);

namespace Convo\Gpt;


interface IChatPromptContainer extends IChatPrompt
{
    
    /**
     * @return IChatAction[]
     */
    public function getActions();
    
    /**
     * @param IChatPrompt $prompt
     */
    public function registerPrompt( $prompt);
    
    /**
     * @return IChatPrompt[]
     */
    public function getPrompts();

    
}
