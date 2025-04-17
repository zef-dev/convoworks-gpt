<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowComponent;

class SimpleMcpPromptTemplate extends AbstractWorkflowComponent implements IConversationElement
{

    private $_name;
    private $_description;
    private $_prompt;
    private $_arguments;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_name      =   $properties['name'];
        $this->_description =   $properties['description'];
        $this->_prompt    =   $properties['prompt'];
        $this->_arguments =   $properties['arguments'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        try {
            /** @var McpServerProcessor $container */
            $container = $this->findAncestor('\Convo\Gpt\Pckg\McpServerProcessor');
            $container->registerPrompt([
                'name' => $this->evaluateString($this->_name),
                'description' => $this->evaluateString($this->_description),
                'arguments' => $this->getService()->evaluateArgs($this->_arguments, $this),
                'template' => $this->_prompt
            ]);
        } catch (DataItemNotFoundException $e) {
            $this->_logger->warning('Failed to find ancestor McpServerProcessor: ' . $e->getMessage());
        }

        // act as reguklar system message
        try {
            /** @var \Convo\Gpt\IMessages $container */
            $container = $this->findAncestor('\Convo\Gpt\IMessages');

            $container->registerMessage([
                'role' => 'system',
                'transient' => true,
                'content' => $this->evaluateString($this->_prompt)
            ]);
        } catch (DataItemNotFoundException $e) {
            $this->_logger->warning('Failed to find ancestor IMessages: ' . $e->getMessage());
        }
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[' . $this->_name . '][' . $this->_description . ']';
    }
}
