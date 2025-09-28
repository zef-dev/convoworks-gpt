<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowComponent;

class SystemMessageElement extends AbstractWorkflowComponent implements IConversationElement
{

    private $_content;
    private $_disableEval;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_content           =   $properties['content'];
        $this->_disableEval       =   $properties['disable_eval'] ?? '';
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        /** @var \Convo\Gpt\IMessages $container */
        $container = $this->findAncestor('\Convo\Gpt\IMessages');

        $disabled = boolval($this->evaluateString($this->_disableEval));

        if ($disabled) {
            $container->registerMessage([
                'role' => 'system',
                'transient' => true,
                'content' => $this->_content
            ]);
        } else {
            $container->registerMessage([
                'role' => 'system',
                'transient' => true,
                'content' => $this->evaluateString($this->_content)
            ]);
        }
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[' . $this->_content . ']';
    }
}
