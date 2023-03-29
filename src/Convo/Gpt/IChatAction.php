<?php declare(strict_types=1);

namespace Convo\Gpt;



interface IChatAction extends IChatPrompt
{
    public function getActionId();
    public function executeAction( $data);
}
