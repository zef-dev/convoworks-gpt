<?php

declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Gpt\GptApiFactory;
use Convo\Core\Expression\ExpressionFunction;
use Convo\Core\Factory\IPlatformProvider;
use Convo\Gpt\Mcp\McpServerPlatform;
use Convo\Gpt\Util;
use Psr\Log\LoggerInterface;

class GptPackageDefinition extends AbstractPackageDefinition implements IPlatformProvider
{
    const NAMESPACE    =    'convo-gpt';

    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    /**
     * @var McpSessionManager
     */
    private $_mcpSessionManager;

    /**
     * @var McpServerPlatform
     */
    private $_mcpPlatform;

    public function __construct(
        LoggerInterface $logger,
        $gptApiFactory,
        $mcpPlatform,
        $mcpSessionManager
    ) {
        $this->_gptApiFactory  =   $gptApiFactory;
        $this->_mcpPlatform    =   $mcpPlatform;
        $this->_mcpSessionManager    =   $mcpSessionManager;

        parent::__construct($logger, self::NAMESPACE, __DIR__);

        $this->registerTemplate(__DIR__ . '/gpt-example-chat.template.json');
        $this->registerTemplate(__DIR__ . '/gpt-site-admin.template.json');
        $this->registerTemplate(__DIR__ . '/deep-research-assistant.template.json');
        $this->registerTemplate(__DIR__ . '/mcp-server-project.template.json');
        $this->registerTemplate(__DIR__ . '/mcp-server-example.template.json');
    }

    public function getFunctions()
    {
        $functions = [];

        $stop_words = [
            "a",
            "about",
            "above",
            "after",
            "again",
            "against",
            "all",
            "am",
            "an",
            "and",
            "any",
            "are",
            "aren't",
            "as",
            "at",
            "be",
            "because",
            "been",
            "before",
            "being",
            "below",
            "between",
            "both",
            "but",
            "by",
            "can't",
            "cannot",
            "could",
            "couldn't",
            "did",
            "didn't",
            "do",
            "does",
            "doesn't",
            "doing",
            "don't",
            "down",
            "during",
            "each",
            "few",
            "for",
            "from",
            "further",
            "had",
            "hadn't",
            "has",
            "hasn't",
            "have",
            "haven't",
            "having",
            "he",
            "he'd",
            "he'll",
            "he's",
            "her",
            "here",
            "here's",
            "hers",
            "herself",
            "him",
            "himself",
            "his",
            "how",
            "how's",
            "i",
            "i'd",
            "i'll",
            "i'm",
            "i've",
            "if",
            "in",
            "into",
            "is",
            "isn't",
            "it",
            "it's",
            "its",
            "itself",
            "let's",
            "me",
            "more",
            "most",
            "mustn't",
            "my",
            "myself",
            "no",
            "nor",
            "not",
            "of",
            "off",
            "on",
            "once",
            "only",
            "or",
            "other",
            "ought",
            "our",
            "ours",
            "ourselves",
            "out",
            "over",
            "own",
            "same",
            "shan't",
            "she",
            "she'd",
            "she'll",
            "she's",
            "should",
            "shouldn't",
            "so",
            "some",
            "such",
            "than",
            "that",
            "that's",
            "the",
            "their",
            "theirs",
            "them",
            "themselves",
            "then",
            "there",
            "there's",
            "these",
            "they",
            "they'd",
            "they'll",
            "they're",
            "they've",
            "this",
            "those",
            "through",
            "to",
            "too",
            "under",
            "until",
            "up",
            "very",
            "was",
            "wasn't",
            "we",
            "we'd",
            "we'll",
            "we're",
            "we've",
            "were",
            "weren't",
            "what",
            "what's",
            "when",
            "when's",
            "where",
            "where's",
            "which",
            "while",
            "who",
            "who's",
            "whom",
            "why",
            "why's",
            "with",
            "won't",
            "would",
            "wouldn't",
            "you",
            "you'd",
            "you'll",
            "you're",
            "you've",
            "your",
            "yours",
            "yourself",
            "yourselves"
        ];


        $functions[] = new ExpressionFunction(
            'tokenize_string',
            function ($text, $stopWords = null) {
                return sprintf('tokenize_string(%s)', var_export($text, true), var_export($stopWords, true));
            },
            function ($args, $text, $stopWords = null) use ($stop_words) {

                if (is_null($stopWords)) {
                    $stopWords = $stop_words;
                }

                $text = wp_strip_all_tags($text);
                $text = strtolower($text);
                $text = preg_replace("#[[:punct:]]#", "", $text);
                $tokens = explode(' ', $text);
                $meaningful_tokens = array_diff($tokens, $stopWords);
                return implode(' ', $meaningful_tokens);
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
                return Util::splitTextIntoChunks($text, $maxChar, $margin);
            }
        );

        $functions[] = new ExpressionFunction(
            'serialize_gpt_messages',
            function ($messages) {
                return sprintf('serialize_gpt_messages(%s)', var_export($messages, true));
            },
            function ($args, $messages) {
                return Util::serializeGptMessages($messages);
            }
        );

        $functions[] = new ExpressionFunction(
            'unserialize_gpt_messages',
            function ($string) {
                return sprintf('unserialize_gpt_messages(%s)', var_export($string, true));
            },
            function ($args, $string) {
                return Util::unserializeGptMessages($string);
            }
        );

        $functions[] = new ExpressionFunction(
            'estimate_tokens',
            function ($content) {
                return sprintf('estimate_tokens(%s)', var_export($content, true));
            },
            function ($args, $content) {
                return Util::estimateTokens($content);
            }
        );

        return $functions;
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
        $BASE_URL = [
            'editor_type' => 'text',
            'editor_properties' => [],
            'defaultValue' => '',
            'name' => 'Base URL',
            'description' => 'Base URL for the API endpoint. If left blank, the default is the OpenAI API endpoint: https://api.openai.com/v1',
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
                    'base_url' => $BASE_URL,
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
                    '_factory' => new class($this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;

                        public function __construct($gptApiFactory)
                        {
                            $this->_gptApiFactory       =   $gptApiFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new ChatCompletionElement($properties, $this->_gptApiFactory);
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
                    'base_url' => $BASE_URL,
                    'max_func_result_tokens' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${8192}',
                        'name' => 'Max Function Result Size',
                        'description' => 'Maximum number of tokens allowed for the function result.',
                        'valueType' => 'string'
                    ],
                    'apiOptions' => [
                        'editor_type' => 'params',
                        'editor_properties' => ['multiple' => true],
                        'defaultValue' => [
                            'model' => 'gpt-4o',
                            'temperature' => '${0.1}',
                            'max_tokens' => '${4096}',
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

                        public function __construct($gptApiFactory)
                        {
                            $this->_gptApiFactory = $gptApiFactory;
                        }
                        public function createComponent($properties, $service)
                        {
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
                    'base_url' => $BASE_URL,
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
                    '_factory' => new class($this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;

                        public function __construct($gptApiFactory)
                        {
                            $this->_gptApiFactory = $gptApiFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new EmbeddingsElement($properties, $this->_gptApiFactory);
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
                    'base_url' => $BASE_URL,
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
                    '_factory' => new class($this->_gptApiFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_gptApiFactory;

                        public function __construct($gptApiFactory)
                        {
                            $this->_gptApiFactory = $gptApiFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new ModerationApiElement($properties, $this->_gptApiFactory);
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
                        'defaultValue' => 'Considering all the prior conversation including the previous summaries, ' .
                            'please generate a concise summary capturing the key points and significant themes up until now. ' .
                            'Please ensure the summary contains all necessary information to understand the context of the current conversation.',
                        'name' => 'System message',
                        'description' => 'Main, system prompt which instructs how to summarize the conversation.',
                        'valueType' => 'string'
                    ],
                    'max_tokens' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${8192}',
                        'name' => 'Max Tokens to Keep',
                        'description' => 'The maximum estimated token count allowed before the conversation is trimmed.',
                        'valueType' => 'string'
                    ],
                    'truncate_to_tokens' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${4096}',
                        'name' => 'Truncate to Tokens',
                        'description' => 'The estimated token count to retain after trimming.',
                        'valueType' => 'string'
                    ],
                    'api_key' => $API_KEY,
                    'base_url' => $BASE_URL,
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
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result variable',
                        'description' => 'The variable that stores the result of the truncation operation.',
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
                        'description' => 'Provides the conversation messages that need to be summarized.',
                        'valueType' => 'class'
                    ],
                    'truncated_flow' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Truncated Flow',
                        'description' => 'The flow to execute if the conversation is truncated.',
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
                    'max_tokens' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${8192}',
                        'name' => 'Max Tokens to Keep',
                        'description' => 'The maximum estimated token count allowed before the conversation is trimmed.',
                        'valueType' => 'string'
                    ],
                    'truncate_to_tokens' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${4096}',
                        'name' => 'Truncate to Tokens',
                        'description' => 'The estimated token count to retain after trimming.',
                        'valueType' => 'string'
                    ],
                    'result_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Result variable',
                        'description' => 'The variable that stores the result of the truncation operation.',
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
                        'description' => 'Provides the conversation messages that need to be summarized.',
                        'valueType' => 'class'
                    ],
                    'truncated_flow' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Truncated Flow',
                        'description' => 'The flow to execute if the conversation is truncated.',
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
                            '<div class="text-long" title="{{component.properties.content}}">{{component.properties.content}}</div>' .
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
                'Groups itself with all child system messages into a single one.',
                [
                    'trim_children' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Trim Child Prompts',
                        'description' => 'Enable this option to join child messages inline.',
                        'valueType' => 'boolean'
                    ],
                    'message_provider' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => ['allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'], 'multiple' => true],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Messages',
                        'description' => 'Executes a sub-flow to provide the messages to be grouped.',
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
                        'filename' => 'group-system-messages-element.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\ChatFunctionElement',
                'Chat Function',
                'Function definition that can be used with Chat Completion API-based elements.',
                [
                    'name' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Function Name',
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
                        'name' => 'Function Parameters',
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
                        'name' => 'Request Data Variable',
                        'description' => 'Variable name used for function arguments',
                        'valueType' => 'string'
                    ],
                    'result_data' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${function_result}',
                        'name' => 'Function Result',
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
                        'name' => 'OK Flow',
                        'description' => 'Flow to be executed when action is requested',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code"><span class="statement">CHAT FUNCTION</span> ' .
                            '<b>{{component.properties.name}}(' .
                            '<span ng-if="!isString(component.properties.parameters)" ng-repeat="(key, val) in component.properties.parameters track by key">' .
                            '{{$index ? ", " : ""}}{{ component.properties.request_data }}.{{ key }}</span>' .
                            '<span ng-if="isString(component.properties.parameters)">{{ component.properties.parameters }}</span>' .
                            ') => {{component.properties.result_data}}</b><div class="text" title="{{component.properties.description}}">{{component.properties.description}}</div></div>'
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
                'Function definition variant which delegates actual execution to a passed callable.',
                [
                    'name' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Function Name',
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
                        'editor_properties' => [],
                        'defaultValue' => [],
                        'name' => 'Function Parameters',
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
                        'name' => 'Default Values',
                        'description' => 'Associative array containing fields and their default values',
                        'valueType' => 'string'
                    ],
                    'execute' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Callable',
                        'description' => 'Function or other callable',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code" title="{{component.properties.description}}"><span class="statement">CHAT FUNCTION</span> ' .
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
                '\Convo\Gpt\Pckg\McpServerProcessor',
                'MCP Processor',
                'Accepts MCP requests and delegates them to its child components.',
                [
                    'name' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => 'Convoworks MCP',
                        'name'             => 'Server Name',
                        'description'      => 'Displayed in initialize→serverInfo.name.',
                        'valueType'        => 'string'
                    ],
                    'version' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => '1.0',
                        'name'             => 'Server Version',
                        'description'      => 'Displayed in initialize→serverInfo.version.',
                        'valueType'        => 'string'
                    ],
                    'tools' => [
                        'editor_type'       => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\\Convo\\Core\\Workflow\\IConversationElement'],
                            'multiple'        => true
                        ],
                        'defaultValue'      => [],
                        'defaultOpen'       => false,
                        'name'             => 'Tools',
                        'description'      => 'Drop conversation elements here that should be exposed as MCP tools/prompts.',
                        'valueType'        => 'class',
                    ],
                    '_factory' => new class($this->_mcpSessionManager) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_mcpSessionManager;

                        public function __construct($mcpSessionManager)
                        {
                            $this->_mcpSessionManager       =   $mcpSessionManager;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new McpServerProcessor($properties, $this->_mcpSessionManager);
                        }
                    },
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">MCP Server</span>' .
                            ' <b>{{component.properties.name}}</b>' .
                            '</div>'
                    ],
                    '_workflow' => 'process',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'mcp-processor.html'
                    ],
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\WpRestProxyFunction',
                'WP REST Proxy Function',
                'Exposes a chat function that forwards its arguments to the internal WordPress REST API ( /wp/v2 ).',
                [
                    'name' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => '',
                        'name'             => 'Function Name',
                        'description'      => 'Unique function identifier.',
                        'valueType'        => 'string',
                    ],
                    'description' => [
                        'editor_type'       => 'desc',
                        'editor_properties' => [],
                        'defaultValue'      => '',
                        'name'             => 'Description',
                        'description'      => 'Brief summary of what the function does.',
                        'valueType'        => 'string',
                    ],
                    'required' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => '${[]}',
                        'name'             => 'Required Parameters',
                        'description'       => 'Expression‑language array of required parameter names, e.g. ${["postId", "status"]}',
                        'valueType'        => 'array',
                    ],
                    'defaults' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => '${[]}',
                        'name'             => 'Default Values',
                        'description'       => 'Expression‑language object of default values, e.g. ${{"per_page":10,"status":"publish"}}',
                        'valueType'        => 'object',
                    ],
                    'method' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => 'GET',
                        'name'             => 'HTTP Method',
                        'description'      => 'HTTP verb used for the REST call (GET, POST, PUT, PATCH, DELETE).',
                        'valueType'        => 'string',
                    ],
                    'endpoint' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => '',
                        'name'             => 'REST Endpoint',
                        'description'      => 'Endpoint relative to /wp/v2 (e.g. "posts", "users/123"). Do not prefix with a slash.',
                        'valueType'        => 'string',
                    ],
                    'pagination' => [
                        'editor_type'       => 'boolean',
                        'editor_properties' => [],
                        'defaultValue'      => false,
                        'name'             => 'Enable Pagination',
                        'description'      => 'If true, wraps the REST response and returns a nextCursor value when additional pages are available.',
                        'valueType'        => 'boolean',
                    ],
                    'parameters' => [
                        'editor_type'       => 'params',
                        'editor_properties' => ['multiple' => true],
                        'defaultValue'      => [],
                        'name'             => 'Function Parameters',
                        'description'      => 'JSON Schema property definitions describing each parameter.',
                        'valueType'        => 'array',
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code"><span class="statement">WP REST PROXY</span> ' .
                            '<b>{{component.properties.name}}(' .
                            '<span ng-if="!isString(component.properties.parameters)" ng-repeat="(key, val) in component.properties.parameters track by key">' .
                            '{{$index ? ", " : ""}}{{ key }}</span>' .
                            '<span ng-if="isString(component.properties.parameters)">{{ component.properties.parameters }}</span>' .
                            ')</b>' .
                            '<div class="text">{{component.properties.method}} {{component.properties.endpoint}}</div>' .
                            '<div class="text">{{component.properties.description}}</div>' .
                            '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' => [
                        'type' => 'file',
                        'filename' => 'wp-rest-proxy-function.html'
                    ]
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Gpt\Pckg\SimpleMcpPromptTemplate',
                'Simple MCP Prompt Template',
                'Defines a reusable MCP prompt template that can be referenced elsewhere in your pipeline.',
                [
                    'name' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => '',
                        'name'              => 'Prompt Name',
                        'description'       => 'Unique identifier for this prompt template.',
                        'valueType'         => 'string',
                    ],
                    'description' => [
                        'editor_type'       => 'desc',
                        'editor_properties' => [],
                        'defaultValue'      => '',
                        'name'              => 'Description',
                        'description'       => 'Human‑readable explanation of what this prompt does.',
                        'valueType'         => 'string',
                    ],
                    'arguments' => [
                        'editor_type'       => 'text',
                        'editor_properties' => [],
                        'defaultValue'      => '${[]}',
                        'name'              => 'Prompt Arguments',
                        'description'       => 'Expression‑language array of argument definition. e.g. ${[{"name":"postId","description":"WP post ID","required":true}]}',
                        'valueType'         => 'array',
                    ],
                    'prompt' => [
                        'editor_type'       => 'desc',
                        'editor_properties' => ['large' => true],
                        'defaultValue'      => '',
                        'name'              => 'Prompt',
                        'description'       => 'Prompt text itself. Use ${paramName} placeholders to reference arguments.',
                        'valueType'         => 'string',
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code"><span class="statement">MCP PROMPT</span> ' .
                            '<b>{{component.properties.name}}(' .
                            '<span ng-if="!isString(component.properties.arguments)" ng-repeat="(key, val) in component.properties.arguments track by key">' .
                            '{{$index ? ", " : ""}}{{ key }}</span>' .
                            '<span ng-if="isString(component.properties.arguments)">{{ component.properties.arguments }}</span>' .
                            ')</b>' .
                            '<div class="text">{{component.properties.description}}</div>' .
                            '<div class="text-long" title="{{component.properties.prompt}}">{{component.properties.prompt}}</div>' .
                            '</div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_descend' => 'true',
                    '_help' => [
                        'type' => 'file',
                        'filename' => 'simple-mcp-prompt-template.html'
                    ]
                ]
            )
        ];
    }


    public function getPlatform($platformId)
    {
        if (strpos($platformId, '.') === false) {
            $search = self::NAMESPACE . '.' . $platformId;
        } else {
            $search = $platformId;
        }

        $this->_logger->info('Searching for platform [' . $platformId . '][' . $search . ']');
        $this->_logger->debug('Comparing to voice [' . $this->_mcpPlatform->getPlatformId() . ']');

        if ($search === $this->_mcpPlatform->getPlatformId()) {
            return $this->_mcpPlatform;
        }

        throw new ComponentNotFoundException('Could not locate platform [' . $platformId . '][' . $search . ']');
    }

    public function getRow()
    {
        $data = parent::getRow();
        $data['platforms'] = [
            McpServerPlatform::PLATFORM_ID => [
                'name' => 'MCP Server',
                'description' => 'MCP server for WordPress',
                'icon_url' => CONVO_GPT_URL . '/assets/mcp-logo-wide.png',
                'config_url' => admin_url('/admin.php?page=convo-gpt.mcp-server.settings&service_id={serviceId}'),
                'enabled' => true,
            ],
        ];

        return $data;
    }
}
