<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IRequestFilterResult;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationProcessor;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Gpt\IChatFunctionContainer;
use Convo\Gpt\Mcp\McpServerCommandRequest;

class McpServerProcessor extends AbstractWorkflowContainerComponent
implements IConversationProcessor, IChatFunctionContainer
{
    /**
     * @var IChatFunction[]
     */
    private $_functions = [];

    private $_name;
    private $_version;

    /**
     * @var IConversationElement[]
     */
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

    public function registerFunction($function)
    {
        $this->_functions[] = $function;
    }

    public function getFunctions(): array
    {
        return $this->_functions;
    }

    public function process(IConvoRequest $request, IConvoResponse $response, IRequestFilterResult $result)
    {
        if (!is_a($request, '\Convo\Gpt\Mcp\McpServerCommandRequest')) {
            $this->_logger->info('Request is not McpServerCommandRequest. Exiting.');
            return;
        }

        /** @var McpServerCommandRequest $request */
        $method = $request->getMethod();

        if (stripos($method, 'notifications') !== false) {
            $this->_handleNotification($method, $request);
            return;
        }

        // Dispatch map
        $handlers = [
            'initialize' => '_handleInitialize',
            'tools/list' => '_handleToolsList',
            'tools/call' => '_handleToolsCall',
            'resources/list' => '_handleResourcesList',
            'resources/templates/list' => '_handleResourceTemplatesList'
        ];

        if (isset($handlers[$method])) {
            $this->{$handlers[$method]}($request, $response);
        } else {
            $this->_logger->warning("Unknown MCP method: $method");
        }
    }

    private function _handleInitialize(McpServerCommandRequest $request, IConvoResponse $response)
    {
        $id = $request->getId();
        $message = [
            "jsonrpc" => "2.0",
            "id" => $id,
            "result" => [
                "protocolVersion" => "2024-11-05",
                "capabilities" => [
                    "tools" => ["listChanged" => true]
                ],
                "serverInfo" => [
                    "name" => $this->evaluateString($this->_name),
                    "version" => $this->evaluateString($this->_version),
                ]
            ]
        ];
        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', $message);
    }

    private function _handleToolsList(McpServerCommandRequest $request, IConvoResponse $response)
    {
        foreach ($this->_tools as $elem) {
            $elem->read($request, $response);
        }

        $tools = array_map([$this, '_convertToolDefinitionToMcp'], array_map(function ($func) {
            return $func->getDefinition();
        }, $this->_functions));

        $message = [
            'jsonrpc' => '2.0',
            'id' => $request->getId(),
            'result' => ['tools' => $tools]
        ];
        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', $message);
    }

    private function _handleToolsCall(McpServerCommandRequest $request, IConvoResponse $response)
    {
        foreach ($this->_tools as $elem) {
            $elem->read($request, $response);
        }

        $id = $request->getId();
        $data = $request->getPlatformData();
        $is_error = false;

        try {
            $function_data = $data['params']['arguments'];
            $function_name = $data['params']['name'];
            $function = $this->_findFunction($function_name);

            $function_result = $function instanceof \Convo\Core\Workflow\IScopedFunction
                ? ($function->restoreParams($pid = $function->initParams()) ?? $function->execute($request, $response, $function_data))
                : $function->execute($request, $response, $function_data);
        } catch (\Throwable $e) {
            $this->_logger->warning($e);
            $function_result = json_encode(['error' => $e->getMessage()]);
            $is_error = true;
        }

        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                "content" => [['type' => 'text', 'text' => $function_result]],
                "isError" => $is_error
            ]
        ]);
    }

    private function _handleResourcesList(McpServerCommandRequest $request, IConvoResponse $response)
    {
        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', [
            'jsonrpc' => '2.0',
            'id' => $request->getId(),
            'result' => ['resources' => []]
        ]);
    }

    private function _handleResourceTemplatesList(McpServerCommandRequest $request, IConvoResponse $response)
    {
        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', [
            'jsonrpc' => '2.0',
            'id' => $request->getId(),
            'result' => ['templates' => []]
        ]);
    }

    private function _handleNotification($method, McpServerCommandRequest $request)
    {
        $data = $request->getPlatformData();
        $requestId = $data['params']['requestId'] ?? null;
        $this->_logger->info('Got notification [' . $request->getServiceId() . '][' . $method . '] for [' . $requestId . ']');
    }


    private function _convertToolDefinitionToMcp($def)
    {
        $name = $def['name'];
        // $def = $toolDef['definition'];

        // Default description if not present
        $description = $def['description'] ?? 'No description provided.';

        // Transform parameters
        $inputSchema = $def['parameters'] ?? [];

        // Add missing schema fields if needed
        if (!isset($inputSchema['type'])) {
            $inputSchema['type'] = 'object';
        }
        if (!isset($inputSchema['required'])) {
            $inputSchema['required'] = [];
        }
        if (!isset($inputSchema['additionalProperties'])) {
            $inputSchema['additionalProperties'] = false;
        }

        // Add $schema to comply with MCP spec
        $inputSchema['$schema'] = 'http://json-schema.org/draft-07/schema#';

        return [
            'name' => $name,
            'description' => $description,
            'inputSchema' => $inputSchema
        ];
    }


    /**
     * @param string $functionName
     * @throws ComponentNotFoundException
     * @return \Convo\Gpt\IChatFunction
     */
    private function _findFunction($functionName)
    {
        foreach ($this->_functions as $function) {
            if ($function->accepts($functionName)) {
                return $function;
            }
        }
        throw new ComponentNotFoundException('Function [' . $functionName . '] not found');
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
