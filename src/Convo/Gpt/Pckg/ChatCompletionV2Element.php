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

class ChatCompletionV2Element extends AbstractWorkflowContainerComponent implements IConversationElement, IChatFunctionContainer
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

    /**
     * @var IConversationElement[]
     */
    private $_functionsDefinition = [];
    
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
    }
    
    public function getFunctions()
    {
        return $this->_functions;
    }
    
    public function registerFunction( $function)
    {
        $this->_functions[] = $function;
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $this->_functions = [];
        foreach ( $this->_functionsDefinition as $elem)   {
            $elem->read( $request, $response);
        }
        
        $system_message =   $this->evaluateString( $this->_properties['system_message']);
        $messages       =   $this->evaluateString( $this->_properties['messages']);

        $messages       =   array_merge(
            [[ 'role' => 'system', 'content' => $system_message]],
            $messages);
        
        $http_response  =  $this->_chatCompletion( $messages);
        $http_response  =  $this->_handleResponse( $http_response, $messages, $request, $response);
        
        $last_message   =  $http_response['choices'][0]['message'];
        $messages[]     =  $last_message;
        array_shift( $messages);
        
        $params         =  $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), [
            'response' => $http_response,
            'messages' => $messages,
            'last_message' => $last_message,
        ]);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    private function _handleResponse( $httpResponse, &$messages, $request, $response)
    {
        $function_name   =   $httpResponse['choices'][0]['message']['function_call']['name'] ?? null;
        $auto_execute    =   true;
        
        if ( $function_name && $auto_execute)
        {
            $messages       =   array_merge(
                $messages,
                [$httpResponse['choices'][0]['message']],
                );
            
            $result = $this->_executeFunction( $httpResponse['choices'][0]['message'], $request, $response);
            
            $messages       =   array_merge(
                $messages,
                [[ 'role' => 'function', 'name' => $function_name, 'content' => $result]]
                );
            $httpResponse   =   $this->_chatCompletion( $messages);
            
            return $this->_handleResponse( $httpResponse, $messages, $request, $response);
        }
        
        return $httpResponse;
    }
    
    private function _executeFunction( $message, $request, $response) 
    {
        $function_name   =   $message['function_call']['name'];
        $data            =   $message['function_call']['arguments'];
        foreach ( $this->_functions as $function) {
            if ( $function->accepts( $function_name)) {
                return $function->execute( $request, $response, $data);
            }
        }
        throw new ComponentNotFoundException( 'Function ['.$function_name.'] not found');
    }
    
    private function _chatCompletion( $messages)
    {
        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        $api        =   $this->_gptApiFactory->getApi( $api_key);
        
        $this->_logger->debug( 'Got messages ============');
        $this->_logger->debug( "\n".json_encode( $messages, JSON_PRETTY_PRINT));
        $this->_logger->debug( '============');
        
        $http_response   =   $api->chatCompletion( $this->_getApiOptions( $messages));
        return $http_response;
    }
    
    private function _getApiOptions( $messages)
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
