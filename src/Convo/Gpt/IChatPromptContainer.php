<?php declare(strict_types=1);

namespace Convo\Gpt;

/**
 * @author Tole
 * @deprecated
 */
interface IChatPromptContainer extends IChatPrompt
{
    
    /**
     * @param IChatPrompt $prompt
     */
    public function registerPrompt( $prompt);
    
    /**
     * @return IChatPrompt[]
     */
    public function getPrompts();

    
}
