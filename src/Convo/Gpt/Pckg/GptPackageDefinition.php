<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Factory\PackageProviderFactory;

class GptPackageDefinition extends AbstractPackageDefinition 
{
    const NAMESPACE    =    'convo-gpt';

    /**
     * @var PackageProviderFactory
     */
    private $_packageProviderFactory;
    
    public function __construct(
        \Psr\Log\LoggerInterface $logger, $packageProviderFactory
    ) {
        $this->_packageProviderFactory  =   $packageProviderFactory;
        
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
                    'text' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Text',
                        'description' => 'Text to be spoken',
                        'valueType' => 'string'
                    ],
                    'buffer' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [
                            'dependency' => "!component.properties.speech"
                        ],
                        'defaultValue' => false,
                        'name' => 'Buffer',
                        'description' => 'Buffer response until first GATHER instruction is issued',
                        'valueType' => 'boolean'
                    ],
                    'break' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '100ms',
                        'name' => 'Break',
                        'description' => 'Pause after speeh (e.g. 100ms)',
                        'valueType' => 'string'
                    ],
                    'numDigits' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 0,
                        'name' => 'Gather Digits',
                        'description' => 'How many digits should we gather',
                        'valueType' => 'int'
                    ],
                    'timeout' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.numDigits > 0"
                        ],
                        'defaultValue' => '5',
                        'name' => 'Timeout',
                        'description' => 'Timeout allows you to set the limit (in seconds) that Twilio will wait for the caller to press another digit or say another word before it sends data',
                        'valueType' => 'string'
                    ],
                    'speech' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Gather Speech',
                        'description' => 'Should gather speech too',
                        'valueType' => 'boolean'
                    ],
                    'enhanced' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [
                            'dependency' => "component.properties.speech"
                        ],
                        'defaultValue' => false,
                        'name' => 'Enhanced',
                        'description' => 'The enhanced attribute instructs <Gather> to use a premium speech model that will improve the accuracy of transcription results',
                        'valueType' => 'boolean'
                    ],
                    'speechModel' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'options' => [ 'default' => 'Default', 'numbers_and_commands' => 'Numbers and commands', 'phone_call' => 'Phone call'],
                            'dependency' => "component.properties.speech"
                        ],
                        'defaultValue' => 'numbers_and_commands',
                        'name' => 'Speech Model',
                        'description' => 'speechModel allows you to select a specific model that is best suited for your use case to improve the accuracy of speech to text.',
                        'valueType' => 'string'
                    ],
                    'speechTimeout' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.speech"
                        ],
                        'defaultValue' => 'auto',
                        'name' => 'Speech timeout',
                        'description' => 'When collecting speech from your caller, speechTimeout sets the limit (in seconds) that Twilio will wait before it stops its speech recognition.',
                        'valueType' => 'string'
                    ],
                    'hints' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false,
                            'dependency' => "component.properties.speech"
                        ],
                        'defaultValue' => '',
                        'name' => 'Hints',
                        'description' => 'Hints for expected speech phrases',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="we-say">Say: <span class="we-say-text">' .
                        ' {{component.properties.text}} </span>' .
                        '<div class="code" ng-if="component.properties.numDigits > 0 || component.properties.speech">' .
                        ' <span class="statement" ng-if="component.properties.numDigits > 0">GATHER DTMF</span>' .
                        ' <span class="statement" ng-if="component.properties.speech">GATHER speech</span> {{component.properties.hints}}' .
                        '</div>' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'voice-response-element.html'
                    ],
                ]
            ),
        ];
    }
}
