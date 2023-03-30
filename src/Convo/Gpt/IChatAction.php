<?php declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

interface IChatAction extends IChatPrompt
{
    public function getActionId();
    
    public function executeAction( $data, IConvoRequest $request, IConvoResponse $response);
}
