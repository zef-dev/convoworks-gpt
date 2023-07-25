<?php declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

interface IChatFunction
{
    
    /**
     * @param string $functionName
     * @return bool
     */
    public function accepts( $functionName);
    
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
     * @param array $data
     * @return array
     */
    public function execute( IConvoRequest $request, IConvoResponse $response, $data);
}
