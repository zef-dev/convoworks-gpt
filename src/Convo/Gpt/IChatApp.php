<?php declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Workflow\IConversationElement;

interface IChatApp extends IConversationElement 
{

    /**
     * @param IChatAction $action
     */
    public function registerAction( $action);
    
    /**
     * @param IChatPrompt $prompt
     */
    public function registerPrompt( $prompt);
    
}
