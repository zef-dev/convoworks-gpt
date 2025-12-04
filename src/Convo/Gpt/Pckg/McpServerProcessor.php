<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IRequestFilterResult;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationProcessor;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Gpt\IChatFunction;
use Convo\Gpt\IChatFunctionContainer;
use Convo\Gpt\Mcp\McpServerCommandRequest;
use Convo\Gpt\Mcp\McpSessionManagerFactory;
use Convo\Gpt\Mcp\SseResponse;
use stdClass;

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
     * @var McpSessionManagerFactory
     */
    private $_mcpSessionManagerFactory;

    public function __construct($properties, $mcpSessionManagerFactory)
    {
        parent::__construct($properties);

        $this->_mcpSessionManagerFactory  =   $mcpSessionManagerFactory;
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
        /** @var SseResponse $response */
        $method = $request->getMethod();
        $session_id = $request->getSessionId();

        if (empty($method) || stripos($method, 'notifications') !== false) {
            $this->_handleNotification($method, $request);
            return;
        }

        // Dispatch map (added completion/complete)
        $handlers = [
            'ping' => '_handlePing',
            'initialize' => '_handleInitialize',
            'tools/list' => '_handleToolsList',
            'tools/call' => '_handleToolsCall',
            'prompts/list' => '_handlePromptsList',
            'prompts/get' => '_handlePromptsGet',
            'resources/list' => '_handleResourcesList',
            'resources/templates/list' => '_handleResourceTemplatesList',
            'completion/complete' => '_handleCompletionComplete'
        ];

        if (isset($handlers[$method])) {
            if ($method !== 'initialize') {
                $this->_mcpSessionManagerFactory->getSessionManager($request->getServiceId())->getActiveSession($session_id);
                $this->_mcpSessionManagerFactory->getSessionManager($request->getServiceId())->getSessionStore()->cleanupInactiveSessions(CONVO_GPT_MCP_SESSION_TIMEOUT);
            }

            $this->{$handlers[$method]}($request, $response);
        } else {
            $this->_logger->warning("Unknown MCP method: $method");
        }
    }

    private function _readTools(McpServerCommandRequest $request, SseResponse $response)
    {
        $this->_functions = [];
        $this->_prompts = [];

        foreach ($this->_tools as $elem) {
            $elem->read($request, $response);
        }
    }

    private function _handlePing(McpServerCommandRequest $request, SseResponse $response)
    {
        $response->setPlatformResponse(new \stdClass());
    }

    private function _handleInitialize(McpServerCommandRequest $request, SseResponse $response)
    {
        $this->_mcpSessionManagerFactory->getSessionManager($request->getServiceId())->activateSession($request->getSessionId());

        $data = $request->getPlatformData();
        $params = $data['params'];
        $req_version = $params['protocolVersion'] ?? null;

        if ($req_version !== '2025-06-18' && $req_version !== "2025-03-26") {  // Updated to new version
            $this->_logger->warning('Unsupported protocol version: ' . $req_version);

            throw new InvalidRequestException("Unsupported protocol version [$req_version]");
        }

        $this->_readTools($request, $response);

        $result = [
            "protocolVersion" => "2025-06-18",
            "capabilities" => [
                "completions" => new \stdClass(),
            ],
            "serverInfo" => [
                "name" => $this->evaluateString($this->_name),
                "version" => $this->evaluateString($this->_version),
            ]
        ];

        if (!empty($this->_functions)) {
            $result['capabilities']['tools']['listChanged'] = true;
        }

        if (!empty($this->_prompts)) {
            $result['capabilities']['prompts']['listChanged'] = true;
        }

        $response->setPlatformResponse($result);
    }

    private function _handlePromptsList(McpServerCommandRequest $request, SseResponse $response)
    {
        $this->_readTools($request, $response);

        $params = $request->getPlatformData()['params'] ?? [];
        $cursor = $params['cursor'] ?? null;

        $prompts = array_map(function ($p) {
            return [
                'name'        => $p['name'],
                'description' => $p['description'] ?? '',
                'arguments'   => $p['arguments'] ?? []
            ];
        }, $this->_prompts);

        $result = ['prompts' => $prompts];

        $response->setPlatformResponse($result);
    }

    private function _handlePromptsGet(McpServerCommandRequest $request, SseResponse $response)
    {
        $id     = $request->getPlatformData()['id'] ?? null;
        $params = $request->getPlatformData()['params'] ?? [];
        $name   = $params['name'] ?? null;
        $args   = $params['arguments'] ?? [];

        $this->_readTools($request, $response);

        try {
            $prompt = $this->_findPrompt($name);
        } catch (DataItemNotFoundException $e) {
            $this->_logger->warning($e->getMessage());
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

        // $msg = ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        $response->setPlatformResponse($result);
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

    private function _throwRpcError($id, $code, $message, McpServerCommandRequest $req)
    {
        $err = [
            'jsonrpc' => '2.0',
            'error'   => ['code' => $code, 'message' => $message]
        ];

        if (trim($id) !== '') {
            $err['id'] = $id;
        }

        $this->_mcpSessionManagerFactory->getSessionManager($req->getServiceId())->enqueueMessage($req->getSessionId(), $err);
    }


    private function _handleToolsList(McpServerCommandRequest $request, SseResponse $response)
    {
        $this->_readTools($request, $response);

        $tools = array_map([$this, '_convertToolDefinitionToMcp'], array_map(function ($func) {
            return $func->getDefinition();
        }, $this->_functions));

        $result = ['tools' => $tools];
        $response->setPlatformResponse($result);
    }

    private function _handleToolsCall(McpServerCommandRequest $request, SseResponse $response)
    {
        $this->_readTools($request, $response);

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
            $this->_logger->warning($e->getMessage());
            $function_result = json_encode(['error' => $e->getMessage()]);
            $is_error = true;
        }

        $result = [
            "content" => [['type' => 'text', 'text' => $function_result]],
            "isError" => $is_error
        ];

        $response->setPlatformResponse($result);
    }

    private function _handleResourcesList(McpServerCommandRequest $request, SseResponse $response)
    {
        $result = ['resources' => new \stdClass()];
        $response->setPlatformResponse($result);
    }

    private function _handleResourceTemplatesList(McpServerCommandRequest $request, SseResponse $response)
    {
        $result = ['templates' => new \stdClass()];
        $response->setPlatformResponse($result);
    }

    private function _handleCompletionComplete(McpServerCommandRequest $request, SseResponse $response)
    {
        // TODO: Implement actual completions based on ref and argument
        $result = [
            "completion" => [
                "values" => [],
                "hasMore" => false
            ]
        ];

        $response->setPlatformResponse($result);
    }

    private function _handleNotification($method, McpServerCommandRequest $request)
    {
        $data = $request->getPlatformData();
        $requestId = $data['params']['requestId'] ?? null;
        $this->_logger->info('Got notification [' . $request->getServiceId() . '][' . $method . '] for [' . $requestId . ']');

        // Explicitly handle notifications/initialized
        if ($method === 'notifications/initialized') {
            $this->_logger->info('Client sent notifications/initialized; session is ready');
            // No response needed for notifications
            return;
        }

        // Handle other notifications if needed
        $this->_logger->debug('Unhandled notification method [' . $method . ']');
    }


    private function _convertToolDefinitionToMcp($def)
    {
        $name = $def['name'];

        $description = $def['description'] ?? 'No description provided.';

        $inputSchema = $def['parameters'] ?? [];

        if (!isset($inputSchema['type'])) {
            $inputSchema['type'] = 'object';
        }
        if (!isset($inputSchema['required'])) {
            $inputSchema['required'] = [];
        }
        if (!isset($inputSchema['additionalProperties'])) {
            $inputSchema['additionalProperties'] = false;
        }

        $tool = [
            'name' => $name,
            'description' => $description,
            'inputSchema' => $inputSchema,
            'annotations' => new \stdClass()
        ];

        if (empty((array)$def['parameters']['properties'])) {
            $tool['inputSchema'] = ['type' => 'object'];
        }

        return $tool;
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
