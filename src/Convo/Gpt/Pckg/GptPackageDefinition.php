<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Factory\PackageProviderFactory;
use Convo\Gpt\GptApiFactory;

class GptPackageDefinition extends AbstractPackageDefinition 
{
    const NAMESPACE    =    'convo-gpt';

    /**
     * @var PackageProviderFactory
     */
    private $_packageProviderFactory;
    
    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;
    
    public function __construct(
        \Psr\Log\LoggerInterface $logger, $packageProviderFactory, $gptApiFactory
    ) {
        $this->_packageProviderFactory  =   $packageProviderFactory;
        $this->_gptApiFactory           =   $gptApiFactory;
        
        parent::__construct( $logger, self::NAMESPACE, __DIR__);
        
        $this->registerTemplate( __DIR__ .'/gpt-examples.template.json');
    }
    
    protected function _initDefintions()
    {
        $API_KEY = [
            'editor_type' => 'text',
            'editor_properties' => [],
            'defaultValue' => null,
            'name' => 'API key',
            'description' => 'Your OpenAI API key',
            'valueType' => 'string'
        ];
        
        return [
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\CompletionElement',
                'x!GPT Completion API',
                'Allows you to execute completion API calls',
                [
                    'prompt' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Prompt',
                        'description' => 'The prompt(s) to generate completions for',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Status variable containing completion response',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                            'model' => 'text-davinci-003',
                            'temperature' => '${0.7}',
                            'max_tokens' => '${256}',
                        ],
                        'name' => 'API options',
                        'description' => 'Completion API options that you can use',
                        'valueType' => 'array'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">COMPLETION API</span>' .
                        '<br>{{component.properties.prompt}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_factory' => new class ( $this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;
                        
                        public function __construct( $gptApiFactory)
                        {
                            $this->_gptApiFactory	   =   $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new CompletionElement( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'completion-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\EmbeddingsElement',
                'GPT Embeddings API',
                'Allows you to create embeddings API',
                [
                    'input' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Input',
                        'description' => 'Input text to generate embeddings for. Can be string or array of string tokens',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Status variable containing embeddings API response',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                            'model' => 'text-embedding-ada-002'
                        ],
                        'name' => 'API options',
                        'description' => 'Embeddings API options that you can use',
                        'valueType' => 'array'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">EMBEDDINGS API</span>' .
                        '<br>{{component.properties.input}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_factory' => new class ( $this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;
                        
                        public function __construct( $gptApiFactory)
                        {
                            $this->_gptApiFactory	   =   $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new EmbeddingsElement( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'completion-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ChatCompletionElement',
                'GPT Chat Completion API',
                'Allows you to execute chat completion API calls',
                [
                    'system_message' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => 'The following is a conversation with an AI assistant. The assistant is helpful, creative, clever, and very friendly. Today is ${date("l, F j, Y")}.',
                        'name' => 'System message',
                        'description' => 'Main, system prompt to be added at the beginning of the conversation.',
                        'valueType' => 'string'
                    ],
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${[]}',
                        'name' => 'Messages',
                        'description' => 'The messages to generate chat completions for, in the chat format.',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Status variable containing completion response',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                            'model' => 'gpt-3.5-turbo',
                            'temperature' => '${0.7}',
                            'max_tokens' => '${256}',
                        ],
                        'name' => 'API options',
                        'description' => 'Chat completion API options that you can use',
                        'valueType' => 'array'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">CHAT COMPLETION API</span>' .
                        '<br>{{component.properties.system_message}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_factory' => new class ( $this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;
                        
                        public function __construct( $gptApiFactory)
                        {
                            $this->_gptApiFactory	   =   $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new ChatCompletionElement( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'chat-completion-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ChatCompletionV2Element',
                'GPT Chat Completion API v2',
                'Allows you to execute chat completion API calls',
                [
                    'system_message' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => 'The following is a conversation with an AI assistant. The assistant is helpful, creative, clever, and very friendly. Today is ${date("l, F j, Y")}.',
                        'name' => 'System message',
                        'description' => 'Main, system prompt to be added at the beginning of the conversation.',
                        'valueType' => 'string'
                    ],
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${[]}',
                        'name' => 'Messages',
                        'description' => 'The messages to generate chat completions for, in the chat format.',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Status variable containing completion response',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                            'model' => 'gpt-3.5-turbo',
                            'temperature' => '${0.7}',
                            'max_tokens' => '${256}',
                        ],
                        'name' => 'API options',
                        'description' => 'Chat completion API options that you can use',
                        'valueType' => 'array'
                    ],
                    'message_provider' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Messages',
                        'description' => 'Message provider which provides conversation we are working with',
                        'valueType' => 'class'
                    ],
                    'functions' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Function',
                        'description' => 'Function definitions',
                        'valueType' => 'class'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">CHAT COMPLETION API</span>' .
                        ' => <b>{{component.properties.result_var}}</b>' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_factory' => new class ( $this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;
                        
                        public function __construct( $gptApiFactory)
                        {
                            $this->_gptApiFactory	   =   $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new ChatCompletionV2Element( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'chat-completion-v2-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ChatAppElement',
                'x!Autonomous Chat',
                'Chat handler which can be configured to autonomously execute actions in the workflow',
                [
                    'system_message' => [
                        'editor_type' => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue' => 'The following is a conversation with an AI assistant. The assistant is helpful, creative, clever, and very friendly. Today is ${date("l, F j, Y")}.',
                        'name' => 'Main prompt',
                        'description' => 'The main prompt to generate completions for. It will be appended with eventual child Prompt elements.',
                        'valueType' => 'string'
                    ],
                    'user_message' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${request.text}',
                        'name' => 'User message',
                        'description' => 'The new user message to append to the conversation.',
                        'valueType' => 'string'
                    ],
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${[]}',
                        'name' => 'All messages',
                        'description' => 'Array containing all messages in the current conversation',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Status variable containing additional operation result information',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                            'model' => 'text-davinci-003',
                            'temperature' => '${0.7}',
                            'max_tokens' => '${256}',
                        ],
                        'name' => 'API options',
                        'description' => 'Completion API options that you can override',
                        'valueType' => 'array'
                    ],
                    'skipChildPrompts' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Skip child prompts',
                        'description' => 'Will use only own prompt, while child Prompts (including action prompts) will not be used',
                        'valueType' => 'boolean'
                    ],
                    'prompts' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Prompts',
                        'description' => 'Child prompts',
                        'valueType' => 'class'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">AUTONOMOUS CHAT</span>' .
                        '<br>{{component.properties.system_message}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_factory' => new class ( $this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;
                        
                        public function __construct( $gptApiFactory)
                        {
                            $this->_gptApiFactory	   =   $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new ChatAppElement( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'chat-app-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\TurboChatAppElement',
                'x!Turbo Chat',
                'Chat handler which can be configured to autonomously execute actions in the workflow',
                [
                    'system_message' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => 'The following is a conversation with an AI assistant. The assistant is helpful, creative, clever, and very friendly. Today is ${date("l, F j, Y")}.',
                        'name' => 'System message',
                        'description' => 'Main, system prompt to be added at the beginning of the conversation.',
                        'valueType' => 'string'
                    ],
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${[]}',
                        'name' => 'Messages',
                        'description' => 'The messages to generate chat completions for, in the chat format.',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Status variable containing additional operation result information',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                            'model' => 'gpt-3.5-turbo',
                            'temperature' => '${0.7}',
                            'max_tokens' => '${256}',
                        ],
                        'name' => 'API options',
                        'description' => 'Chat Completion API options that you can override',
                        'valueType' => 'array'
                    ],
                    'skipChildPrompts' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Skip child prompts',
                        'description' => 'Will use only own prompt, while child Prompts (including action prompts) will not be used',
                        'valueType' => 'boolean'
                    ],
                    'prompts' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Prompts',
                        'description' => 'Child prompts',
                        'valueType' => 'class'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">TURBO CHAT</span>' .
                        '<br>{{component.properties.system_message}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_factory' => new class ( $this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;
                        
                        public function __construct( $gptApiFactory)
                        {
                            $this->_gptApiFactory	   =   $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new TurboChatAppElement( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'turbo-chat-app-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ChatFunctionElement',
                'Chat Function',
                'Function definition that can be used with Completion API based elements',
                [
                    'name' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Function name',
                        'description' => 'Unique function name',
                        'valueType' => 'string'
                    ],
                    'description' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Description',
                        'description' => 'Functions description',
                        'valueType' => 'string'
                    ],
                    'parameters' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                        ],
                        'name' => 'Function parameters',
                        'description' => 'Function parameters definition',
                        'valueType' => 'array'
                    ],
                    'defaults' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${{}}',
                        'name' => 'Defaults',
                        'description' => 'Associative array of default values for fucntion parameters',
                        'valueType' => 'string'
                    ],
                    'required' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${[]}',
                        'name' => 'Required',
                        'description' => 'Array of required fields',
                        'valueType' => 'string'
                    ],
                    'request_data' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'data',
                        'name' => 'Request data variable',
                        'description' => 'Variable name used for function arguments',
                        'valueType' => 'string'
                    ],
                    'result_data' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${function_result}',
                        'name' => 'Function result',
                        'description' => 'Expression that will evaluate to the function result',
                        'valueType' => 'string'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed when action is requested',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code" title="{{component.properties.description}}"><span class="statement">CHAT FUNCTION</span>' .
                        ' <b>{{component.properties.name}}('.
                        '<span ng-repeat="(key,val) in component.properties.parameters track by key">{{$index ? ", " : ""}}{{ component.properties.request_data }}.{{ key }}</span>'.
                        ') => {{component.properties.result_data}}</b>' .
                        '</div>'
                    ],
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'simple-chat-action-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\SimpleChatActionElement',
                'x!Simple Chat Action',
                'Allows you to define definition (prompt) and execute action in the workflow',
                [
                    'action_id' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Action ID',
                        'description' => 'Unique action identifier',
                        'valueType' => 'string'
                    ],
                    'title' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Prompt Title',
                        'description' => 'Title for the prompt section',
                        'valueType' => 'string'
                    ],
                    'content' => [
                        'editor_type' => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue' => '',
                        'name' => 'Prompt Content',
                        'description' => 'Content of the prompt section',
                        'valueType' => 'string'
                    ],
                    'action_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'action',
                        'name' => 'Action Variable Name',
                        'description' => 'Variable containing action data which is available in OK flow',
                        'valueType' => 'string'
                    ],
                    'result' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${[]}',
                        'name' => 'Result',
                        'description' => 'Variable which evaluates to a result of the executed action',
                        'valueType' => 'string'
                    ],
                    'autoActivate' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Automatically activated',
                        'description' => 'Applicable for actions without paramaters, will automatically prepended to the conversation',
                        'valueType' => 'boolean'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed when action is requested',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">CHAT ACTION</span>' .
                        ' <b>{{component.properties.title}}</b>' .
                        '<br>action_id = {{component.properties.action_id}}' .
//                        ' <br>  {{component.properties.content}} ' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'simple-chat-action-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\SimplePromptElement',
                'Simple Prompt',
                'This element allows you to split complex prompt into several, managable sections.',
                [
                    'title' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Prompt Title',
                        'description' => 'Title for the prompt section',
                        'valueType' => 'string'
                    ],
                    'content' => [
                        'editor_type' => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue' => '',
                        'name' => 'Prompt Content',
                        'description' => 'Content of the prompt section',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">PROMPT</span>' .
                        ' <b>{{component.properties.title}}</b>' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'simple-prompt-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\SystemMessageElement',
                'System Message',
                '',
                [
                    'content' => [
                        'editor_type' => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue' => '',
                        'name' => 'Message content',
                        'description' => 'Content of the system message',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">SYSTEM</span>' .
                        ' <br>{{component.properties.content}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
//                     '_help' =>  [
//                         'type' => 'file',
//                         'filename' => 'simple-prompt-element.html'
//                     ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ConversationMessagesElement',
                'Conversation Messages',
                '',
                [
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Messages',
                        'description' => 'Expression which evaluates to conversation messages array',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">CONVERSATION</span>' .
                        ' <b>{{component.properties.messages}}</b>' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
//                     '_help' =>  [
//                         'type' => 'file',
//                         'filename' => 'simple-prompt-element.html'
//                     ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\PromptSectionElement',
                'Prompt Section',
                'This element allows nesting prompts',
                [
                    'title' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Prompt Title',
                        'description' => 'Title for the prompt section',
                        'valueType' => 'string'
                    ],
                    'content' => [
                        'editor_type' => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue' => '',
                        'name' => 'Prompt Content',
                        'description' => 'Content of the prompt section',
                        'valueType' => 'string'
                    ],
                    'prompts' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Prompts',
                        'description' => 'Child prompt definition which will be appended as a subsection in the resulting prompt',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">PROMPT SECTION</span>' .
                        ' <b>{{component.properties.title}}</b>' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'prompt-section-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ValidationErrorElement',
                'x!Validation Error',
                'Stops the execution and signals the Chat App that action request is not valid',
                [
                    'message' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'Not valid request',
                        'name' => 'Message',
                        'description' => 'Validation message',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">VALIDATION ERROR</span>' .
                        ' {{component.properties.message}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'validation-error-element.html'
                    ],
                ]
            ),
        ];
    }
}
