<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\ConvoServiceInstance;
use Convo\Core\Workflow\AbstractScopedFunction;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IChatFunction;

class ExternalChatFunctionElement extends AbstractWorkflowContainerComponent implements IConversationElement
{

    private $_name;
    private $_description;
    private $_parameters;
    private $_defaults;
    private $_required;
    private $_execute;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_name            =   $properties['name'];
        $this->_description     =   $properties['description'];
        $this->_parameters      =   $properties['parameters'];
        $this->_defaults        =   $properties['defaults'] ?? [];
        $this->_required        =   $properties['required'];
        $this->_execute         =   $properties['execute'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        /** @var \Convo\Gpt\IChatFunctionContainer $container */
        $container = $this->findAncestor('\Convo\Gpt\IChatFunctionContainer');

        $data = [
            'name' => $this->evaluateString($this->_name),
            'description' => $this->evaluateString($this->_description),
            'parameters' => $this->evaluateString($this->_parameters),
            'defaults' => $this->evaluateString($this->_defaults),
            'required' => $this->evaluateString($this->_required),
            'execute' => $this->evaluateString($this->_execute),
        ];

        $chat_function = new class($data, $this->getService()) extends AbstractScopedFunction implements IChatFunction {

            /**
             * @var ConvoServiceInstance
             */
            private $_convoServiceInstance;
            private $_functionData;
            public function __construct($functionData, $service)
            {
                $this->_functionData = $functionData;
                $this->_convoServiceInstance = $service;
            }

            public function accepts($functionName)
            {
                return $functionName === $this->getName();
            }

            public function getName()
            {
                return $this->_functionData['name'];
            }

            public function getDefinition()
            {
                // Return the definition of the function, as an array

                return [
                    'name' => $this->getName(),
                    'description' => $this->_functionData['description'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => empty($this->_functionData['parameters']) ? new \stdClass : $this->_functionData['parameters'],
                        'required' => $this->_functionData['required'],
                    ],
                ];
            }

            public function execute(IConvoRequest $request, IConvoResponse $response, $data)
            {

                $data       =   json_decode($data, true);
                $data       =   array_merge($this->_functionData['defaults'], $data);
                $result     =   $this->_functionData['execute']($data);

                if (is_string($result)) {
                    return $result;
                }
                return json_encode($result);
            }
        };

        $container->registerFunction($chat_function);
    }


    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[' . $this->_name . ']';
    }
}
