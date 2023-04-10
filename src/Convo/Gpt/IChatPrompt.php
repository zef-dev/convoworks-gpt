<?php declare(strict_types=1);

namespace Convo\Gpt;


interface IChatPrompt 
{
    /**
     * @return string
     */
    public function getPromptContent();
    
    /**
     * @return IChatAction[]
     */
    public function getActions();
    
    /**
     * @return int
     */
    public function getDepth();
}
