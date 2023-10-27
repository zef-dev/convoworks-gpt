<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\GptApiFactory;
use Convo\Gpt\IChatFunction;
use Convo\Gpt\IChatFunctionContainer;
use Convo\Core\ComponentNotFoundException;
use Convo\Gpt\IMessages;
use Convo\Gpt\RefuseFunctionCallException;

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
     * @var IConversationElement[]
     */
    private $_functionsDefinition = [];

    /**
     * @var IConversationElement[]
     */
    private $_messagesDefinition = [];
    
    private $_callStack  =  [];
    
    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);
        
        $this->_gptApiFactory  =	$gptApiFactory;
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
        
        if ( isset( $properties['functions'])) {
            foreach ( $properties['functions'] as $element) {
                $this->_functionsDefinition[] = $element;
                $this->addChild($element);
            }
        }
        
        if ( isset( $properties['message_provider'])) {
            foreach ( $properties['message_provider'] as $element) {
                $this->_messagesDefinition[] = $element;
                $this->addChild($element);
            }
        }
    }
    
    public function getFunctions()
    {
        return $this->_functions;
    }
    
    public function registerFunction( $function)
    {
        $this->_functions[] = $function;
    }
    
    public function registerMessage( $message)
    {
        $this->_messages[] = $message;
    }
    
    public function getMessages()
    {
        return $this->_messages;
    }
    
    public function getConversation()
    {
        return array_map( function ( $item) {
            unset( $item['transient']);
            return $item;
        }, $this->_messages);
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $this->_callStack = [];
        
        $this->_functions = [];
        foreach ( $this->_functionsDefinition as $elem)   {
            $elem->read( $request, $response);
        }
        
        $this->_messages = [];
        foreach ( $this->_messagesDefinition as $elem)   {
            $elem->read( $request, $response);
        }
        
//         $system_message =   $this->evaluateString( $this->_properties['system_message']);
//         $messages       =   $this->evaluateString( $this->_properties['messages']);

//         $messages       =   array_merge(
//             [[ 'role' => 'system', 'content' => $system_message]],
//             $messages);
        
        $http_response  =  $this->_chatCompletion( $this->_messages);
        $http_response  =  $this->_handleResponse( $http_response, $this->_messages, $request, $response);
        
        $last_message   =  $http_response['choices'][0]['message'];
        $this->registerMessage( $last_message);
//         array_shift( $messages);
        
        $params         =  $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), [
            'response' => $http_response,
            'messages' => array_filter( $this->_messages, function ( $message) {
                if ( !isset( $message['transient']) || !$message['transient']) {
                    return true;
                }
            }),
            'last_message' => $last_message,
        ]);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    private function _handleResponse( $httpResponse, &$messages, $request, $response)
    {
        $function_name   =   $httpResponse['choices'][0]['message']['function_call']['name'] ?? null;
        $function_data   =   $httpResponse['choices'][0]['message']['function_call']['arguments'] ?? null;
        $auto_execute    =   true;
        
        if ( $function_name && $auto_execute)
        {
            $messages       =   array_merge(
                $messages,
                [$httpResponse['choices'][0]['message']],
            );
            
            try {
                
                $function_data = self::processJsonWithConstants( $function_data);
                $this->_logger->debug( 'Got processed JSON ['.$function_data.']');
                $this->_registerExecution( $function_name, $function_data);
                $function   =   $this->_findFunction( $function_name);
                $result     =   $function->execute( $request, $response, $function_data);
            } catch ( RefuseFunctionCallException $e) {
                throw $e;
            } catch ( \Exception $e) {
                $this->_logger->warning( $e);
                $result = json_encode( [ 'error' => $e->getMessage()]);
            }
            
            $messages       =   array_merge(
                $messages,
                [[ 'role' => 'function', 'name' => $function_name, 'content' => $result]]
                );
            $httpResponse   =   $this->_chatCompletion( $messages);
            
            return $this->_handleResponse( $httpResponse, $messages, $request, $response);
        }
        
        return $httpResponse;
    }
    
    public static function processJsonWithConstants($json)
    {
        // Placeholder for escaped double quotes
        $PLACEHOLDER = "***convo-json-placeholder***";
        // Pattern representing escaped double quotes in JSON
        $PATTERN = '\\\\"';
        
        // Temporarily replace escaped double quotes with a placeholder
        $json = str_replace($PATTERN, $PLACEHOLDER, $json);
        
        $json = preg_replace_callback('/("[^"]*")|(\b[A-Z_]+\b)/', function ($matches) {
            // If part of a string, return as is
            if ($matches[1]) return $matches[1];
            
            $constantName = $matches[2];
            
            // Check if it is a defined constant
            if (defined($constantName)) {
                // Get the value of the constant
                $constantValue = constant($constantName);
                
                // Replace with the actual value of the constant, ensuring it's JSON-encoded
                return is_numeric($constantValue) ? $constantValue : json_encode($constantValue);
            }
            
            // If it's not a defined constant, wrap as string value
            return '"'.$constantName.'"';
        }, $json);
            
        // Restore the escaped double quotes
        $json = str_replace($PLACEHOLDER, $PATTERN, $json);
        
        return $json;
    }
    
    private function _registerExecution( $functionName, $functionData)
    {
        $MAX_ATTEMPTS = 3;
        $key = md5($functionName.'-'.$functionData);
        if ( !isset( $this->_callStack[$key])) {
            $this->_callStack[$key] = 0;
        }
        
        if ( $this->_callStack[$key] > $MAX_ATTEMPTS) {
            throw new RefuseFunctionCallException( 'You were already warned to stop invoking ['.$functionName.'] after ['.$this->_callStack[$key].'] attempts. Breaking the further execution.');
        }
        
        $this->_callStack[$key] = $this->_callStack[$key] + 1;
        
        if ( $this->_callStack[$key] > $MAX_ATTEMPTS) {
            throw new \Exception( 'Caution: Please avoid invoking the ['.$functionName.'] function with arguments ['.$functionData.'] again! Compliance is crucial. Attempted invocations have reached ['.$this->_callStack[$key].'], while the maximum allowed is ['.$MAX_ATTEMPTS.'].');
        }
    }
    
    /**
     * @param string $functionName
     * @throws ComponentNotFoundException
     * @return \Convo\Gpt\IChatFunction
     */
    private function _findFunction( $functionName) 
    {
        foreach ( $this->_functions as $function) {
            if ( $function->accepts( $functionName)) {
                return $function;
            }
        }
        throw new ComponentNotFoundException( 'Function ['.$functionName.'] not found');
    }
    
    private function _chatCompletion( $messages)
    {
        $messages = array_map( function ( $item) {
            unset( $item['transient']);
            return $item;
        }, $messages);
        
        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        $api        =   $this->_gptApiFactory->getApi( $api_key);
        
        $this->_logger->debug( 'Got messages ============');
        $this->_logger->debug( "\n".json_encode( $messages, JSON_PRETTY_PRINT));
        $this->_logger->debug( '============');
        
        $http_response   =   $api->chatCompletion( $this->_buildApiOptions( $messages));
        return $http_response;
    }
    
    private function _buildApiOptions( $messages)
    {
        $options = $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);
        
        if ( count( $this->getFunctions())) {
            $options['functions'] = [];
            foreach ( $this->getFunctions() as $function) {
                $options['functions'][] = $function->getDefinition();
            }
        }
        
        $options['messages'] = $messages;
        
        return $options;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }




}
