<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\GptApiFactory;
use Convo\Gpt\IChatFunction;
use Convo\Gpt\IChatFunctionContainer;
use Convo\Gpt\IMessages;
use Convo\Gpt\RefuseFunctionCallException;
use Convo\Gpt\Util;
use RuntimeException;

class ChatCompletionV2Element extends AbstractWorkflowContainerComponent implements IConversationElement, IChatFunctionContainer, IMessages
{

    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    /**
     * @var IConversationElement[]
     */
    private $_ok = [];

    /**
     * @var IChatFunction[]
     */
    private $_functions = [];

    private $_messages = [];

    /**
     * @var array
     * All new message objects that are created during one call (repoetitive function calls).
     * If there is a singkle response meesage, it will contain just it.
     */

    private $_newMessages = [];

    /**
     * @var IConversationElement[]
     */
    private $_newMessageFlow = [];

    /**
     * @var IConversationElement[]
     */
    private $_messagesDefinition = [];

    private $_callStack  =  [];

    public function __construct($properties, $gptApiFactory)
    {
        parent::__construct($properties);

        $this->_gptApiFactory  =    $gptApiFactory;

        foreach ($properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }

        if (isset($properties['new_message_flow'])) {
            foreach ($properties['new_message_flow'] as $element) {
                $this->_newMessageFlow[] = $element;
                $this->addChild($element);
            }
        }

        if (isset($properties['message_provider'])) {
            foreach ($properties['message_provider'] as $element) {
                $this->_messagesDefinition[] = $element;
                $this->addChild($element);
            }
        }
    }

    public function getFunctions()
    {
        return $this->_functions;
    }

    public function registerFunction($function)
    {
        $this->_functions[] = $function;
    }

    public function registerMessage($message)
    {
        $this->_messages[] = $message;
    }

    public function getMessages()
    {
        return array_merge($this->_messages, $this->_newMessages);
    }

    public function getMessagesClean()
    {
        $ALLOWED = ['role', 'content', 'refusal', 'tool_calls', 'function_call', 'tool_call_id', 'name'];
        return array_map(function ($item) use ($ALLOWED) {
            return array_intersect_key($item, array_flip($ALLOWED));
        }, $this->getMessages());
    }
    public function getConversation()
    {
        return array_filter($this->getMessages(), function ($message) {
            if (!isset($message['transient']) || !$message['transient']) {
                return true;
            }
        });
    }


    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $this->_callStack = [];

        $this->_prepareConversationContext($request, $response);

        $http_response  =  $this->_chatCompletion();
        $http_response  =  $this->_handleResponse($http_response, $request, $response);

        $last_message   =  $http_response['choices'][0]['message'];
        $this->_readNewMessageFlow($request, $response, $last_message, $http_response);

        $params         =  $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam($this->evaluateString($this->_properties['result_var']), [
            'response' => $http_response,
            'messages' => $this->getConversation(),
            'last_message' => $last_message,
        ]);

        foreach ($this->_ok as $elem) {
            $elem->read($request, $response);
        }
    }

    private function _prepareConversationContext(IConvoRequest $request, IConvoResponse $response)
    {
        $this->_functions = [];
        $this->_messages = [];
        $this->_newMessages = [];

        foreach ($this->_messagesDefinition as $elem) {
            $elem->read($request, $response);
        }
    }

    private function _readNewMessageFlow(IConvoRequest $request, IConvoResponse $response, $message, $httpResponse = null)
    {
        $this->_logger->debug('Handling new message role:[' . $message['role'] . ']');

        $this->_newMessages[] = $message;

        $params         =  $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam($this->evaluateString($this->_properties['result_var']), [
            'response' => $httpResponse,
            'last_message' => $message,
        ]);

        foreach ($this->_newMessageFlow as $elem) {
            $elem->read($request, $response);
        }
    }

    private function _handleResponse($httpResponse, $request, $response)
    {
        if (isset($httpResponse['choices'][0]['message']['tool_calls']) && !empty($httpResponse['choices'][0]['message']['tool_calls'])) {
            $this->_readNewMessageFlow($request, $response, $httpResponse['choices'][0]['message'], $httpResponse);

            foreach ($httpResponse['choices'][0]['message']['tool_calls'] as $tool_call) {
                $tool_id         =   $tool_call['id'] ?? null;
                $function_name   =   $tool_call['function']['name'] ?? null;
                $function_data   =   $tool_call['function']['arguments'] ?? null;

                // $function_data = json_decode($function_data, true);
                $error = json_last_error();
                if ($error !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON parsing error: ' . json_last_error_msg());
                }

                if ($function_name) {
                    $this->_logger->info('Going to execute tool function [' . $tool_id . '][' . $function_name . ']');

                    try {

                        if (strpos($function_data, '"callback": "defined"') === false && strpos($function_data, '"callback": "constant"') === false) {
                            $this->_logger->debug('Going to preprocess JSON [' . $function_data . ']');
                            $function_data = Util::processJsonWithConstants($function_data);
                        }

                        $this->_logger->debug('Got processed JSON [' . $function_data . ']');
                        $this->_registerExecution($function_name, $function_data);
                        $function   =   $this->_findFunction($function_name);
                        $function_data = json_decode($function_data, true);
                        if ($function instanceof \Convo\Core\Workflow\IScopedFunction) {
                            /** @var \Convo\Core\Workflow\IScopedFunction $function */
                            $id = $function->initParams();
                            /** @var IChatFunction $function */
                            $result     =   $function->execute($request, $response, $function_data);
                            $function->restoreParams($id);
                        } else {
                            $result     =   $function->execute($request, $response, $function_data);
                        }

                        if (isset($this->_properties['max_func_result_tokens']) && !empty($this->_properties['max_func_result_tokens'])) {
                            $MAX_RESULT_TOKENS = $this->evaluateString($this->_properties['max_func_result_tokens']);

                            $result_size = Util::estimateTokens($result);
                            $this->_logger->debug('Function [' . $function_name . '] returned result [' . $result_size . '] tokens. Max allowed is [' . $MAX_RESULT_TOKENS . ']');
                            if ($result_size > $MAX_RESULT_TOKENS) {
                                throw new RuntimeException('Function [' . $function_name . '] returned too large result [' . $result_size . ']. If possible, adjust the function arguments to return less data. Maximum allowed is [' . $MAX_RESULT_TOKENS . '] tokens.');
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->_logger->warning($e->getMessage());
                        $result = json_encode(['error' => $e->getMessage()]);
                    }

                    $this->_readNewMessageFlow($request, $response, ['role' => 'tool', 'tool_call_id' => $tool_id, 'content' => $result]);
                }
            }
            $this->_prepareConversationContext($request, $response);
            $httpResponse   =   $this->_chatCompletion();

            return $this->_handleResponse($httpResponse, $request, $response);
        }

        return $httpResponse;
    }

    private function _chatCompletion()
    {
        $api_key    =   $this->evaluateString($this->_properties['api_key']);
        $base_url   =   $this->evaluateString($this->_properties['base_url'] ?? null);
        $api        =   $this->_gptApiFactory->getApi($api_key, $base_url);
        $http_response   =   $api->chatCompletion($this->_buildApiOptions());
        return $http_response;
    }

    private function _buildApiOptions()
    {
        $options = $this->getService()->evaluateArgs($this->_properties['apiOptions'], $this);

        if (count($this->getFunctions())) {
            $options['tools'] = [];
            foreach ($this->getFunctions() as $function) {
                // $this->_logger->debug('Registering function [' . json_encode($function->getDefinition(), JSON_PRETTY_PRINT) . ']');
                $options['tools'][] = [
                    'type' => 'function',
                    'function' => $function->getDefinition(),
                ];
            }
        }

        $options['messages'] = $this->getMessagesClean();

        return $options;
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

    private function _registerExecution($functionName, $functionData)
    {
        $MAX_ATTEMPTS = 3;
        $key = md5($functionName . '-' . $functionData);
        if (!isset($this->_callStack[$key])) {
            $this->_callStack[$key] = 0;
        }

        if ($this->_callStack[$key] > $MAX_ATTEMPTS) {
            throw new RefuseFunctionCallException('You were already warned to stop invoking [' . $functionName . '] with arguments [' . $functionData . '] after [' . $this->_callStack[$key] . '] attempts. Breaking the further execution.');
        }

        $this->_callStack[$key] = $this->_callStack[$key] + 1;

        if ($this->_callStack[$key] > $MAX_ATTEMPTS) {
            throw new \Exception('Caution: Please avoid invoking the [' . $functionName . '] function with arguments [' . $functionData . '] again! Compliance is crucial. Attempted invocations have reached [' . $this->_callStack[$key] . '], while the maximum allowed is [' . $MAX_ATTEMPTS . '].');
        }
    }


    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[]';
    }
}
