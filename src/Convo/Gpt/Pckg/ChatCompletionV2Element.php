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
use Convo\Gpt\Util;

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
        return array_merge( $this->_messages, $this->_newMessages);
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

        $this->_prepeareConversationContext( $request, $response);

        $http_response  =  $this->_chatCompletion();
        $http_response  =  $this->_handleResponse( $http_response, $request, $response);

        $last_message   =  $http_response['choices'][0]['message'];
        $this->registerMessage( $last_message);

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

    private function _prepeareConversationContext( IConvoRequest $request, IConvoResponse $response)
    {
        $this->_functions = [];
        $this->_messages = [];

        foreach ( $this->_messagesDefinition as $elem)   {
            $elem->read( $request, $response);
        }

        foreach ( $this->_functionsDefinition as $elem)   {
            $elem->read( $request, $response);
        }
    }

    private function _handleResponse( $httpResponse, $request, $response)
    {
        $function_name   =   $httpResponse['choices'][0]['message']['function_call']['name'] ?? null;
        $function_data   =   $httpResponse['choices'][0]['message']['function_call']['arguments'] ?? null;
        $auto_execute    =   true;

        if ( $function_name && $auto_execute)
        {
            $this->_newMessages[] = $httpResponse['choices'][0]['message'];

            try {

                if ( strpos( $function_data, '"callback": "defined"') === false && strpos( $function_data, '"callback": "constant"') === false) {
                    $this->_logger->debug( 'Going to preprocess JSON ['.$function_data.']');
                    $function_data = Util::processJsonWithConstants( $function_data);
                }

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

            $this->_newMessages[] = [ 'role' => 'function', 'name' => $function_name, 'content' => $result];

            $this->_prepeareConversationContext( $request, $response);
            $httpResponse   =   $this->_chatCompletion();

            return $this->_handleResponse( $httpResponse, $request, $response);
        }

        return $httpResponse;
    }

    private function _chatCompletion()
    {
        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        $api        =   $this->_gptApiFactory->getApi( $api_key);
        $http_response   =   $api->chatCompletion( $this->_buildApiOptions());
        return $http_response;
    }

    private function _buildApiOptions()
    {
        $options = $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);

        if ( count( $this->getFunctions())) {
            $options['functions'] = [];
            foreach ( $this->getFunctions() as $function) {
                $options['functions'][] = $function->getDefinition();
            }
        }

        $options['messages'] = array_map( function ( $item) {
            unset( $item['transient']);
            return $item;
        }, $this->getMessages());

        return $options;
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

    private function _registerExecution( $functionName, $functionData)
    {
        $MAX_ATTEMPTS = 3;
        $key = md5($functionName.'-'.$functionData);
        if ( !isset( $this->_callStack[$key])) {
            $this->_callStack[$key] = 0;
        }

        if ( $this->_callStack[$key] > $MAX_ATTEMPTS) {
            throw new RefuseFunctionCallException( 'You were already warned to stop invoking ['.$functionName.'] with arguments ['.$functionData.'] after ['.$this->_callStack[$key].'] attempts. Breaking the further execution.');
        }

        $this->_callStack[$key] = $this->_callStack[$key] + 1;

        if ( $this->_callStack[$key] > $MAX_ATTEMPTS) {
            throw new \Exception( 'Caution: Please avoid invoking the ['.$functionName.'] function with arguments ['.$functionData.'] again! Compliance is crucial. Attempted invocations have reached ['.$this->_callStack[$key].'], while the maximum allowed is ['.$MAX_ATTEMPTS.'].');
        }
    }


    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }




}
