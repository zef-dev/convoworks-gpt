<?php declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

/**
 * @deprecated
 */
interface IChatAction extends IChatPrompt
{
    
    /**
     * @return string
     */
    public function getActionId();
    
    /**
     * @return bool
     */
    public function autoActivate();
    
    /**
     * @param string $actionId
     * @return bool
     */
    public function accepts( $actionId);
    
    
    /**
     * @param array $data
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     * @return array
     * @throws ValidationException
     */
    public function executeAction( $data, IConvoRequest $request, IConvoResponse $response);
}
