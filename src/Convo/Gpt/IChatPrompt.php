<?php declare(strict_types=1);

namespace Convo\Gpt;



use Convo\Core\Workflow\IConversationElement;

interface IChatPrompt extends IConversationElement 
{

    public function getPrompt();
}
