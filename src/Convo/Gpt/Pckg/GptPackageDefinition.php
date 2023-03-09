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
    }
    
    public function getFunctions()
    {
        $functions          =   [];
        return $functions;
    }
    
    protected function _initEntities()
    {
        $entities  =    [];
        return $entities;
    }
    
    protected function _initDefintions()
    {
        return [
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\CompletionElement',
                'GPT Completion',
                'GPT Completion API call',
                [
                    'api_key' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${API_KEY}',
                        'name' => 'API key',
                        'description' => '',
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
                    'model' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'options' => [ 
                                'text-davinci-003' => 'text-davinci-003', 
                                'text-curie-001' => 'text-curie-001', 
                                'text-babbage-001' => 'text-babbage-001',
                                'text-ada-001' => 'text-ada-001',
                            ],
                        ],
                        'defaultValue' => 'text-davinci-003',
                        'name' => 'Model',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'prompt' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => '${request.text}',
                        'name' => 'Prompt',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'temperature' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 0.7,
                        'name' => 'Temperature',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'max_tokens' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 256,
                        'name' => 'Max tokens',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'top_p' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 1,
                        'name' => 'Top p',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'frequency_penalty' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 0,
                        'name' => 'Frequency penalty',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'presence_penalty' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 0,
                        'name' => 'Presence penalty',
                        'description' => '',
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
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    'empty' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Empty response',
                        'description' => 'Flow to be executed if response was empty',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">COMPLETION</span>' .
                        ' <br>  {{component.properties.prompt}} ' .
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
        }
//                     '_help' =>  [
//                         'type' => 'file',
//                         'filename' => 'voice-response-element.html'
//                     ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ChatCompletionElement',
                'GPT Chat Completion',
                'GPT Chat Completion API call',
                [
                    'api_key' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${API_KEY}',
                        'name' => 'API key',
                        'description' => '',
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
                    'model' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'options' => [ 
                                'gpt-3.5-turbo' => 'gpt-3.5-turbo', 
                                'gpt-3.5-turbo-0301' => 'gpt-3.5-turbo-0301', 
                            ],
                        ],
                        'defaultValue' => 'gpt-3.5-turbo',
                        'name' => 'Model',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'messages',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'temperature' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 0.7,
                        'name' => 'Temperature',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'max_tokens' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 256,
                        'name' => 'Max tokens',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'top_p' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 1,
                        'name' => 'Top p',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'frequency_penalty' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 0,
                        'name' => 'Frequency penalty',
                        'description' => '',
                        'valueType' => 'string'
                    ],
                    'presence_penalty' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 0,
                        'name' => 'Presence penalty',
                        'description' => '',
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
                        'description' => 'Flow to be executed if operation is finished with result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">CHAT COMPLETION</span>' .
                        ' <br>  {{component.properties.prompt}} ' .
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
                    }
//                     '_help' =>  [
//                         'type' => 'file',
//                         'filename' => 'voice-response-element.html'
//                     ],
                ]
            ),
        ];
    }
}
