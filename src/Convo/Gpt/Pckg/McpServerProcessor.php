<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IRequestFilterResult;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationProcessor;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Gpt\IChatFunctionContainer;
use Convo\Gpt\Mcp\McpServerCommandRequest;
use Convo\Gpt\Mcp\McpSessionManager;

class McpServerProcessor extends AbstractWorkflowContainerComponent
implements IConversationProcessor, IChatFunctionContainer
{
    private $_prompts = [];

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

    public function registerPrompt($prompt)
    {
        $this->_prompts[] = $prompt;
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
        $session_id = $request->getSessionId();
        // $service_id = $request->getServiceId();

        if (empty($method) || stripos($method, 'notifications') !== false) {
            $this->_handleNotification($method, $request);
            return;
        }

        // Dispatch map
        $handlers = [
            'ping' => '_handlePing',
            'initialize' => '_handleInitialize',
            'tools/list' => '_handleToolsList',
            'tools/call' => '_handleToolsCall',
            'prompts/list' => '_handlePromptsList',
            'prompts/get' => '_handlePromptsGet',
            'resources/list' => '_handleResourcesList',
            'resources/templates/list' => '_handleResourceTemplatesList'
        ];

        if (isset($handlers[$method])) {
            if ($method !== 'initialize') {
                $this->_mcpSessionManager->getActiveSession($session_id);
            }

            $this->{$handlers[$method]}($request, $response);
        } else {
            $this->_logger->warning("Unknown MCP method: $method");
        }
    }

    private function _handlePing(McpServerCommandRequest $request, IConvoResponse $response)
    {
        $message = [
            "jsonrpc" => "2.0",
            "id" => $request->getId(),
            "result" => new \stdClass()
        ];

        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', $message);
    }

    private function _handleInitialize(McpServerCommandRequest $request, IConvoResponse $response)
    {
        $this->_mcpSessionManager->activateSession($request->getSessionId());

        $data = $request->getPlatformData();
        $params = $data['params'];
        $req_version = $params['protocolVersion'] ?? null;

        if ($req_version !== '2024-11-05') {
            $this->_logger->warning('Unsupported protocol version: ' . $req_version);

            $error = [
                "jsonrpc" => "2.0",
                "id" => $request->getId(),
                "error" => [
                    "code" => -32602,
                    "message" => "Unsupported protocol version",
                    "data" => [
                        "supported" => ["2024-11-05"],
                        "requested" => $req_version
                    ]
                ]
            ];

            $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', $error);
            return;
        }

        $id = $request->getId();
        $message = [
            "jsonrpc" => "2.0",
            "id" => $id,
            "result" => [
                "protocolVersion" => "2024-11-05",
                "capabilities" => [
                    "tools"   => ["listChanged" => true],
                    "prompts" => ["listChanged" => true]
                ],
                "serverInfo" => [
                    "name" => $this->evaluateString($this->_name),
                    "version" => $this->evaluateString($this->_version),
                ]
            ]
        ];

        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', $message);
    }

    private function _handlePromptsList(McpServerCommandRequest $request, IConvoResponse $response)
    {
        foreach ($this->_tools as $elem) {
            $elem->read($request, $response);
        }

        // TODO: support pagination if you ever need it
        $prompts = array_map(function ($p) {
            return [
                'name'        => $p['name'],
                'description' => $p['description'] ?? '',
                'arguments'   => $p['arguments'] ?? []
            ];
        }, $this->_prompts);

        $msg = [
            'jsonrpc' => '2.0',
            'id'      => $request->getId(),
            'result'  => ['prompts' => $prompts]
        ];
        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', $msg);
    }

    private function _handlePromptsGet(McpServerCommandRequest $request, IConvoResponse $response)
    {
        $id     = $request->getId();
        $params = $request->getPlatformData()['params'] ?? [];
        $name   = $params['name'] ?? null;
        $args   = $params['arguments'] ?? [];

        foreach ($this->_tools as $elem) {
            $elem->read($request, $response);
        }

        try {
            $prompt = $this->_findPrompt($name);
        } catch (DataItemNotFoundException $e) {
            $this->_logger->warning($e);
            return $this->_throwRpcError($id, -32602, $e->getMessage(), $request);
        }


        // --- 1. simple required‑field check -------------------------------
        foreach ($prompt['arguments'] as $argDef) {
            if (($argDef['required'] ?? false) && !isset($args[$argDef['name']])) {
                return $this->_throwRpcError(
                    $id,
                    -32602,
                    "Missing required argument '{$argDef['name']}'",
                    $request
                );
            }
        }

        // --- 2. substitute placeholders -----------------------------------
        $text = $this->evaluateString($prompt['template'], $args);

        // --- 3. package as messages array ---------------------------------
        $result = [
            'description' => $prompt['description'] ?? '',
            'messages'    => [[
                'role'    => 'user',
                'content' => ['type' => 'text', 'text' => $text]
            ]]
        ];

        $msg = ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        $this->_mcpSessionManager->enqueueEvent($request->getSessionId(), 'message', $msg);
    }

    private function _findPrompt($name)
    {
        foreach ($this->_prompts as $prompt) {
            if ($prompt['name'] === $name) {
                return $prompt;
            }
        }
        throw new DataItemNotFoundException("Prompt '{$name}' not found");
    }

    private function _throwRpcError($id, $code, $message, $req)
    {
        $err = [
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => ['code' => $code, 'message' => $message]
        ];
        $this->_mcpSessionManager->enqueueEvent($req->getSessionId(), 'message', $err);
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
            $this->_logger->debug('Got processed JSON [' . $function_name . '][' . $function_data . ']');
            $is_scoped = $function instanceof \Convo\Core\Workflow\IScopedFunction;
            if ($is_scoped) {
                $pid = $function->initParams();
                $function_result = $function->execute($request, $response, $function_data);
                $function->restoreParams($pid);
            } else {
                $function_result = $function->execute($request, $response, $function_data);
            }
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

        if (empty((array)$def['parameters']['properties'])) {
            return [
                'name' => $name,
                'description' => $description,
                'inputSchema' => [
                    'type' => 'object',
                ]
            ];
        }

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
