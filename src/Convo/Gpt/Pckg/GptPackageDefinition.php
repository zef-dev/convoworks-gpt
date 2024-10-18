<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Gpt\GptApiFactory;
use Convo\Core\Expression\ExpressionFunction;

class GptPackageDefinition extends AbstractPackageDefinition
{
    const NAMESPACE    =    'convo-gpt';

    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger, $gptApiFactory
    ) {
        $this->_gptApiFactory           =   $gptApiFactory;

        parent::__construct( $logger, self::NAMESPACE, __DIR__);

        $this->registerTemplate( __DIR__ .'/gpt-example-chat.template.json');
        $this->registerTemplate( __DIR__ .'/gpt-site-assistant.template.json');
    }

    public function getFunctions()
    {
        $functions = [];

        $stop_words = [
            "a", "about", "above", "after", "again", "against", "all", "am", "an", "and", "any", "are", "aren't", "as", "at",
            "be", "because", "been", "before", "being", "below", "between", "both", "but", "by", "can't", "cannot", "could",
            "couldn't", "did", "didn't", "do", "does", "doesn't", "doing", "don't", "down", "during", "each", "few", "for", "from",
            "further", "had", "hadn't", "has", "hasn't", "have", "haven't", "having", "he", "he'd", "he'll", "he's", "her", "here",
            "here's", "hers", "herself", "him", "himself", "his", "how", "how's", "i", "i'd", "i'll", "i'm", "i've", "if", "in",
            "into", "is", "isn't", "it", "it's", "its", "itself", "let's", "me", "more", "most", "mustn't", "my", "myself", "no",
            "nor", "not", "of", "off", "on", "once", "only", "or", "other", "ought", "our", "ours", "ourselves", "out", "over", "own",
            "same", "shan't", "she", "she'd", "she'll", "she's", "should", "shouldn't", "so", "some", "such", "than", "that", "that's",
            "the", "their", "theirs", "them", "themselves", "then", "there", "there's", "these", "they", "they'd", "they'll", "they're",
            "they've", "this", "those", "through", "to", "too", "under", "until", "up", "very", "was", "wasn't", "we", "we'd", "we'll",
            "we're", "we've", "were", "weren't", "what", "what's", "when", "when's", "where", "where's", "which", "while", "who",
            "who's", "whom", "why", "why's", "with", "won't", "would", "wouldn't", "you", "you'd", "you'll", "you're", "you've",
            "your", "yours", "yourself", "yourselves"
        ];


        $functions[] = new ExpressionFunction(
            'tokenize_string',
            function ($text, $stopWords=null) {
                return sprintf('tokenize_string(%s)', var_export( $text, true), var_export( $stopWords, true));
            },
            function($args, $text, $stopWords=null) use ( $stop_words) {

                if ( is_null( $stopWords)) {
                    $stopWords = $stop_words;
                }

                $text = wp_strip_all_tags( $text);
                $text = strtolower( $text);
                $text = preg_replace("#[[:punct:]]#", "", $text);
                $tokens = explode(' ', $text);
                $meaningful_tokens = array_diff( $tokens, $stopWords);
                return implode( ' ', $meaningful_tokens);
            }
        );

        $functions[] = new ExpressionFunction(
            'split_text_into_chunks', // Function name as used in expressions
            function ($text, $maxChar = 30000, $margin = 1000) {
                // Compile-time function, returns the PHP code to be executed
                // This is typically used for caching or optimizing the expressions
                return sprintf('split_text_into_chunks(%s, %s, %s)', $text, $maxChar, $margin);
            },
            function ($arguments, $text, $maxChar = 30000, $margin = 1000) {
                // Runtime function, the actual PHP function to execute
                return self::splitTextIntoChunks($text, $maxChar, $margin);
            }
        );

        $functions[] = new ExpressionFunction(
            'serialize_gpt_messages',
            function ($messages) {
                return sprintf('serialize_gpt_messages(%s)', var_export($messages, true));
            },
            function ($args, $messages) {
                // Map each message object to a readable string
                return implode("\n\n", array_map(function($message) {
                    $role = ucfirst($message['role']); // Capitalize the role
                    return sprintf("%s: %s", $role, $message['content']);
                }, $messages));
            }
        );


        return $functions;
    }


    public static function splitTextIntoChunks($text, $maxChar, $margin) {
        $chunks = [];
        if (empty($text)) {
            return $chunks;
        }
        $currentChunk = "";

        $parts = preg_split('/(\.|\?|!)\s+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            if (strlen($currentChunk . $part) > $maxChar) {
                $chunks[] = $currentChunk;
                $currentChunk = $part;
            } else {
                $currentChunk .= $part;
            }
        }

        if (!empty(trim($currentChunk))) {
            if ( strlen( $currentChunk) > $margin) {
                $chunks[] = $currentChunk;
            } else {
                // append to the last one if it is a small chunk
                $last_index = count( $chunks) - 1;
                $chunks[$last_index] .= $currentChunk;
            }

        }

        return $chunks;
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
                'Facilitates chat completion API calls with advanced capabilities.',
                [
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Variable storing the API completion response.',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => ['multiple' => true],
                        'defaultValue' => [
                            'model' => 'gpt-3.5-turbo',
                            'temperature' => '${0.7}',
                            'max_tokens' => '${256}',
                        ],
                        'name' => 'API options',
                        'description' => 'Configuration options for the chat completion API.',
                        'valueType' => 'array'
                    ],
                    'message_provider' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => ['allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'], 'multiple' => true],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Messages',
                        'description' => 'Executes a sub-flow to provide messages (context) for the chat completion API.',
                        'valueType' => 'class'
                    ],
                    'functions' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => ['allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'], 'multiple' => true],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Function (Deprecated)',
                        'description' => 'Dynamically registers available functions the agent can utilize.',
                        'valueType' => 'class'
                    ],
                    'new_message_flow' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => ['allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'], 'multiple' => true],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'New message flow',
                        'description' => 'Executed after each new message is created.',
                        'valueType' => 'class'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => ['allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'], 'multiple' => true],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Executed once the operation is completed and the result variable is ready for use.',
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
                    '_factory' => new class($this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory {
                    private $_gptApiFactory;

                    public function __construct($gptApiFactory) {
                        $this->_gptApiFactory = $gptApiFactory;
                    }
                    public function createComponent($properties, $service) {
                        return new ChatCompletionV2Element($properties, $this->_gptApiFactory);
                    }
                    },
                    '_help' => [
                        'type' => 'file',
                        'filename' => 'chat-completion-v2-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\EmbeddingsElement',
                'GPT Embeddings API',
                'Allows the creation of an embeddings API.',
                [
                    'input' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Input',
                        'description' => 'Input text for generating embeddings. Can be a string or an array of string tokens.',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'Status variable containing the embeddings API response.',
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
                        'description' => 'Options for the Embeddings API.',
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
                        'description' => 'Flow to execute if the operation completes and the result variable is available for use.',
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
                            $this->_gptApiFactory = $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new EmbeddingsElement( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'embeddings-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ModerationApiElement',
                'GPT Moderation API',
                'Validate input with the OpenAI Moderation API',
                [
                    'input' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Input',
                        'description' => 'The input text to be moderated',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result Variable Name',
                        'description' => 'The status variable containing the moderation API response',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [
                            'model' => 'text-moderation-latest'
                        ],
                        'name' => 'API options',
                        'description' => 'Options for the OpenAI Moderation API',
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
                        'description' => 'The flow to be executed if the operation is finished with the result variable available for use',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">MODERATION API</span>' .
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
                            $this->_gptApiFactory = $gptApiFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new ModerationApiElement( $properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'moderation-api-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\GptQueryGeneratorElement',
                'GPT Query Generator',
                'Generates relevant questions from a given conversation to enhance GPT chat completion-based interactions by querying a knowledge database.',
                [
                    'system_message' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => "Given the following conversation between a user and an AI assistant:\n\nUser: [User's message]\nAssistant: [Assistant's response]\n\nPlease generate max 3 relevant questions that can be used to query a vector database for further information. These questions should capture the main topics and key details discussed in the conversation.\n\n1: [Generated Question]\n2: [Generated Question]\n- ...\n\nNote: If no relevant questions can be generated from the conversation, please respond with the \"NA\". If the last user's message is a simple courtesy (e.g., \"thanks\" or \"goodbye\"), please indicate that no questions were produced.\n",
                        'name' => 'System Message',
                        'description' => 'Initial prompt setting the context and format for the conversation.',
                        'valueType' => 'string'
                    ],
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Messages',
                        'description' => 'Array of messages from the GPT chat completion-based conversation which serves as the context for question generation.',
                        'valueType' => 'array'
                    ],
                    'messages_count' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${6}',
                        'name' => 'Messages Count',
                        'description' => 'Defines the number of recent messages to be considered for question generation.',
                        'valueType' => 'integer'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'questions',
                        'name' => 'Result Variable',
                        'description' => 'Variable to store the generated questions.',
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
                        'name' => 'API Options',
                        'description' => 'Defines specific parameters for the GPT chat completion API to fine-tune question generation.',
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
                        'name' => 'OK Flow',
                        'description' => 'Sequence of elements executed after successful question generation, allowing for follow-up actions.',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">GPT GENERATE QUESTIONS</span>' .
                        '<br>{{component.properties.messages}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_factory' => new class ($this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;

                        public function __construct($gptApiFactory)
                        {
                            $this->_gptApiFactory = $gptApiFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new GptQueryGeneratorElement($properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'gpt-query-generator-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ConversationMessagesElement',
                'Conversation Messages',
                'Manages and provides the entire conversation to the GPT Chat Completion API v2, ensuring consistent context throughout the interaction.',
                [
                    'messages' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Messages',
                        'description' => 'Expression that evaluates to the conversation messages array.',
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
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'conversation-messages-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\MessagesLimiterElement',
                'GPT Messages Limiter',
                'Limits messages size by summarizing the oldest ones.',
                [
                    'system_message' => [
                        'editor_type' => 'desc',
                        'editor_properties' => [],
                        'defaultValue' => 'Considering all the prior conversation including the previous summaries, '.
                        'please generate a concise summary capturing the key points and significant themes up until now. '.
                        'Please ensure the summary contains all necessary information to understand the context of the current conversation.',
                        'name' => 'System message',
                        'description' => 'Main, system prompt which instructs how to summarize the conversation.',
                        'valueType' => 'string'
                    ],
                    'max_count' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${20}',
                        'name' => 'Max messages to keep',
                        'description' => 'The maximum number of messages before the conversation is summarized.',
                        'valueType' => 'string'
                    ],
                    'truncate_to' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${10}',
                        'name' => 'Truncate message count',
                        'description' => 'The number of messages to keep after summarization.',
                        'valueType' => 'string'
                    ],
                    'include_functions' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => array(),
                        'defaultValue' => false,
                        'name' => 'Include functions',
                        'description' => 'If true, function results will also be summarized',
                        'valueType' => 'boolean'
                    ],
                    'api_key' => $API_KEY,
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => ['multiple' => true],
                        'defaultValue' => [
                            'model' => 'gpt-3.5-turbo',
                            'temperature' => '${0.7}',
                            'max_tokens' => '${256}',
                        ],
                        'name' => 'API options',
                        'description' => 'Chat completion API options for summarization.',
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
                        'description' => 'Provides the conversation messages that need to be summarized.',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">GPT MESSAGES LIMITER</span>' .
                        ' => <b>{{component.properties.max_count}}</b>, <b>{{component.properties.truncate_to}}</b>' .
                        '<br>{{component.properties.system_message}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_factory' => new class($this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;

                        public function __construct($gptApiFactory)
                        {
                            $this->_gptApiFactory = $gptApiFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new MessagesLimiterElement($properties, $this->_gptApiFactory);
                        }
                    },
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'messages-limiter-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\SimpleMessagesLimiterElement',
                'Simple Messages Limiter',
                'Limits messages size by simply removing the oldest ones.',
                [
                  'max_count' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${20}',
                        'name' => 'Max Messages to Keep',
                        'description' => 'The maximum number of messages allowed before the conversation is trimmed.',
                        'valueType' => 'string'
                    ],
                    'truncate_to' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${10}',
                        'name' => 'Truncate Message Count',
                        'description' => 'The number of messages to retain after trimming.',
                        'valueType' => 'string'
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
                        'description' => 'Provides the conversation messages to be trimmed.',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">SIMPLE MESSAGES LIMITER</span>' .
                        ' => <b>{{component.properties.max_count}}</b>, <b>{{component.properties.truncate_to}}</b>' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'simple-message-limiter-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\SystemMessageElement',
                'System Message',
                'Defines a system-generated message within the chat context.',
                [
                    'content' => [
                        'editor_type' => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue' => '',
                        'name' => 'Message content',
                        'description' => 'The text content of the system message.',
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
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'system-message-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\SystemMessageGroupElement',
                'Group System Messages',
                'Groups own with all child system messages into single one.',
                [
                    'content' => [
                        'editor_type' => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue' => '',
                        'name' => 'Message content',
                        'description' => 'The text content of the system message.',
                        'valueType' => 'string'
                    ],
                    'trim_children' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Trim child prompts',
                        'description' => 'Enabling trim, you can join child messages inline',
                        'valueType' => 'boolean'
                    ],
                    'message_provider' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => ['allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'], 'multiple' => true],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Messages',
                        'description' => 'Executes a sub-flow to provide messages which should be grouped',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">GROUP SYSTEM</span>' .
                        ' <br>{{component.properties.content}}' .
                        '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'system-message-element.html'
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
                        'description' => 'Function description',
                        'valueType' => 'string'
                    ],
                    'parameters' => [
                        'editor_type' => 'params',
                        'editor_properties' => [
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'Function parameters',
                        'description' => 'Function parameter definitions',
                        'valueType' => 'array'
                    ],
                    'defaults' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${{}}',
                        'name' => 'Defaults',
                        'description' => 'Associative array of default values for function parameters',
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
                        'template' =>
                        '<div class="code" title="{{component.properties.description}}"><span class="statement">CHAT FUNCTION</span> '.
                            '<b>{{component.properties.name}}(' .
                            // Conditional to check if parameters is a string or an array
                            '<span ng-if="!isString(component.properties.parameters)" ng-repeat="(key, val) in component.properties.parameters track by key">'.
                            '{{$index ? ", " : ""}}{{ component.properties.request_data }}.{{ key }}</span>' .
                            // If parameters is a string, display it directly
                            '<span ng-if="isString(component.properties.parameters)">{{ component.properties.parameters }}</span>' .
                            ') => {{component.properties.result_data}}</b>
                        </div>'
                    ],
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'chat-function-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ExternalChatFunctionElement',
                'External Chat Function',
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
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Description',
                        'description' => 'Function description',
                        'valueType' => 'string'
                    ],
                    'parameters' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                        ],
                        'defaultValue' => [],
                        'name' => 'Function parameters',
                        'description' => 'Function parameter definitions',
                        'valueType' => 'array'
                    ],
                    'required' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Required',
                        'description' => 'Array of required fields',
                        'valueType' => 'string'
                    ],
                    'defaults' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Default values',
                        'description' => 'Associative array containing fields and their default values',
                        'valueType' => 'string'
                    ],
                    'execute' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Callable',
                        'description' => 'Function on other callable.',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code" title="{{component.properties.description}}"><span class="statement">CHAT FUNCTION</span> '.
                            '<b>{{component.properties.name}}(' .
                            '{{ component.properties.parameters }}' .
                            ')  => {{ component.properties.execute }}</b>
                        </div>'
                    ],
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'external-chat-function-element.html'
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
                'x!Simple Prompt',
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
                '\Convo\Gpt\Pckg\PromptSectionElement',
                'x!Prompt Section',
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
