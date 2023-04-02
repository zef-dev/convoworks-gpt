<?php declare(strict_types=1);

namespace Convo\Gpt;


interface IChatApp 
{

    /**
     * @param IChatAction $action
     */
    public function registerAction( $action);
    
    /**
     * @return IChatAction[]
     */
    public function getActions();
    
    /**
     * @param IChatPrompt $prompt
     */
    public function registerPrompt( $prompt);
    
}
