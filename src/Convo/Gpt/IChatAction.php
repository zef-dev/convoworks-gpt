<?php declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

interface IChatAction extends IChatPrompt
{
    
    /**
     * @param string $actionId
     * @return bool
     */
    public function accepts( $actionId);
    
    
    /**
     * @param array $data
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     * @throws ValidationException
     */
    public function executeAction( $data, IConvoRequest $request, IConvoResponse $response);
}
