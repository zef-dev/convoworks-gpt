<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IRequestFilterResult;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationProcessor;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Gpt\Mcp\McpServerCommandRequest;

class McpServerProcessor extends AbstractWorkflowContainerComponent implements IConversationProcessor
{

    /**
     * @var IConversationElement[]
     */
    private $_name;
    private $_version;
    private $_tools;

    /**
     * @var McpSessionManager
     */
    private $_mcpSessionManager;

    public function __construct($properties, $mcpSessionManager)
    {
        parent::__construct($properties);

        $this->_mcpSessionManager  =   $mcpSessionManager;
        $this->_name     =   $properties['name'];
        $this->_version  =   $properties['version'];
        $this->_tools    =   $properties['tools'];
        foreach ($this->_tools as $elem) {
            $this->addChild($elem);
        }
    }

    public function process(IConvoRequest $request, IConvoResponse $response, IRequestFilterResult $result)
    {
        if (!is_a($request, '\Convo\Gpt\Mcp\McpServerCommandRequest')) {
            $this->_logger->info('Request is not McpServerCommandRequest. Exiting.');
            return;
        }

        $data = $request->getPlatformData();
        $id = $request->getId();
        $method = $request->getMethod();
        $this->_logger->debug('Command: ' . $method . ' - ' . $id);

        if ($method === 'initialize') {
            $message = [
                "jsonrpc" => "2.0",
                "id" => $id,
                "result" => [
                    "protocolVersion" => "2024-11-05",
                    "capabilities" => [
                        "tools" => [
                            "listChanged" => true
                        ]
                    ],
                    "serverInfo" => [
                        "name" => $this->evaluateString($this->_name),
                        "version" => $this->evaluateString($this->_version),
                    ]
                ]
            ];

            $this->_mcpSessionManager->accept($request->getSessionId(), 'message', $message);
        } else  if ($method === 'tools/list') {
            $message = [
                'jsonrpc' => '2.0',
                // 'method' => 'tools/list',
                'id' => $id,
                'result' => [
                    'tools' => [
                        [
                            'name' => 'say_hello',
                            'description' => 'Say hello to someone',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                        'description' => 'Name of the person'
                                    ]
                                ],
                                'required' => ['name'],
                                'additionalProperties' => false,
                                '$schema' => 'http://json-schema.org/draft-07/schema#'
                            ]
                        ]
                    ]
                ]
            ];

            $this->_mcpSessionManager->accept($request->getSessionId(), 'message', $message);
        }

        // $this->_logger->debug('Processing OK');
        // foreach ($this->_tools as $elem) {
        //     $elem->read($request, $response);
        // }

    }

    public function filter(IConvoRequest $request)
    {
        $result = new DefaultFilterResult();

        if (!is_a($request, '\Convo\Gpt\Mcp\McpServerCommandRequest')) {
            $this->_logger->info('Request is not McpServerCommandRequest. Exiting.');
            return $result;
        }

        /** @var McpServerCommandRequest $request */

        // $data = $request->getPlatformData();
        $id = $request->getId();
        $method = $request->getMethod();
        $this->_logger->debug('Command: ' . $method . ' - ' . $id);

        $result->setSlotValue('method', $method);

        return $result;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[]';
    }
}
