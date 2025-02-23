<?php

declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use \Convo\Core\Workflow\IScopedFunction;

interface IChatFunction extends IScopedFunction
{

    /**
     * @param string $functionName
     * @return bool
     */
    public function accepts($functionName);

    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function getDefinition();

    /**
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     * @param string $data
     * @return string
     */
    public function execute(IConvoRequest $request, IConvoResponse $response, $data);
}
