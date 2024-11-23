<?php

use PHPUnit\Framework\TestCase;
use Convo\Gpt\Util;

class TrimMessagesTest extends TestCase
{


    /**
     * @dataProvider shouldNotChangeEmptyArray
     * @dataProvider shouldNotTruncate
     * @dataProvider shouldTruncateAtSize
     * @dataProvider shouldKeepUserAndAssistantTogether
     * @dataProvider shouldKeepGroupsTogether
     */
    public function testTruncateMessages($messages, $expected, $maxMessages, $truncateTo)
    {
        $result = Util::truncate($messages, $maxMessages, $truncateTo);

        $this->assertEquals($expected, $result, "Failed asserting that two arrays are equal.");
    }

    // NEW DATA
    public function shouldNotChangeEmptyArray()
    {
        return [[
            'messages' => [],
            'expected' => [],
            'maxMessages' => 5,
            'truncateTo' => 3,
        ]];
    }

    public function shouldNotTruncate()
    {
        return [[
            'messages' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'Thnx'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'Thnx'],
            ],
            'maxMessages' => 6,
            'truncateTo' => 6,
        ], [
            'messages' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'Thnx'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'Thnx'],
            ],
            'maxMessages' => 5,
            'truncateTo' => 3,
        ]];
    }

    public function shouldTruncateAtSize()
    {
        return [[
            'messages' => [
                ['role' => 'assistant', 'content' => 'How can I help?'],
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => 'Sorry, I can\'t help.'],
                ['role' => 'user', 'content' => 'I thought you can'],
                ['role' => 'assistant', 'content' => 'Weel, not'],
                ['role' => 'user', 'content' => 'No prob'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => 'Sorry, I can\'t help.'],
                ['role' => 'user', 'content' => 'I thought you can'],
                ['role' => 'assistant', 'content' => 'Weel, not'],
                ['role' => 'user', 'content' => 'No prob'],
            ],
            'maxMessages' => 5,
            'truncateTo' => 5,
        ], [
            'messages' => [
                ['role' => 'assistant', 'content' => 'How can I help?'],
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => 'Sorry, I can\'t help.'],
                ['role' => 'user', 'content' => 'I thought you can'],
                ['role' => 'assistant', 'content' => 'Weel, not'],
                ['role' => 'user', 'content' => 'No prob'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'I thought you can'],
                ['role' => 'assistant', 'content' => 'Weel, not'],
                ['role' => 'user', 'content' => 'No prob'],
            ],
            'maxMessages' => 5,
            'truncateTo' => 3,
        ], [
            'messages' => [
                ['role' => 'assistant', 'content' => 'How can I help?', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'How are you?'],
                ['role' => 'assistant', 'content' => 'I am fine, thank you.', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'My name is Tihomir'],
                ['role' => 'assistant', 'content' => 'That\'s a great name', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'Thanks'],
                ['role' => 'assistant', 'content' => 'You are welcome', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'I live in Croatia'],
                ['role' => 'assistant', 'content' => 'Great country.', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'Yes.'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'Thanks'],
                ['role' => 'assistant', 'content' => 'You are welcome', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'I live in Croatia'],
                ['role' => 'assistant', 'content' => 'Great country.', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'Yes.'],
            ],
            'maxMessages' => 9,
            'truncateTo' => 5,
        ]];
    }

    public function shouldKeepUserAndAssistantTogether()
    {
        return [[
            'messages' => [
                ['role' => 'assistant', 'content' => 'How can I help?', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'Hello!'],
                ['role' => 'assistant', 'content' => 'Hi there!', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'How are you?'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'Hello!'],
                ['role' => 'assistant', 'content' => 'Hi there!', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'How are you?'],
            ],
            'maxMessages' => 3,
            'truncateTo' => 2,
        ]];
    }

    public function shouldKeepGroupsTogether()
    {
        return [[
            'messages' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 65°F'],
                ['role' => 'user', 'content' => 'Thank you.'],
                ['role' => 'assistant', 'content' => 'You are welcoem'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'Thank you.'],
                ['role' => 'assistant', 'content' => 'You are welcoem'],
            ],
            'maxMessages' => 5,
            'truncateTo' => 2,
        ], [
            'messages' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 65°F'],
                ['role' => 'user', 'content' => 'Thank you.'],
                ['role' => 'assistant', 'content' => 'You are welcoem'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'What is the weather?'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 65°F'],
                ['role' => 'user', 'content' => 'Thank you.'],
                ['role' => 'assistant', 'content' => 'You are welcoem'],
            ],
            'maxMessages' => 5,
            'truncateTo' => 4,
        ], [
            'messages' => [
                ['role' => 'user', 'content' => 'How are you?'],
                ['role' => 'assistant', 'content' => 'I am fine, thank you.'],
                ['role' => 'user', 'content' => 'What is the weather now'],
                ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'What about tomorrow?'],
                ['role' => 'assistant', 'content' => 'Let me check again.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'Thanks'],
                ['role' => 'assistant', 'content' => 'You are welocme'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'What is the weather now'],
                ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'What about tomorrow?'],
                ['role' => 'assistant', 'content' => 'Let me check again.', 'tool_calls' => [['name' => 'weather_api_call']]],
                ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                ['role' => 'user', 'content' => 'Thanks'],
                ['role' => 'assistant', 'content' => 'You are welocme'],
            ],
            'maxMessages' => 10,
            'truncateTo' => 7,
        ], [
            'messages' => [
                ['role' => 'assistant', 'content' => 'Hey there! What can I do for you?', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'generate events calendar'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => 'The events calendar for 2025 has been successfully generated.', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'how many lectures have we imported?'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => 'We have a total of 70 lectures imported as private posts in the system.', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'cool'],
                ['role' => 'assistant', 'content' => 'If there is anything else you need, feel free to ask!', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'what do we have in uploads folder?'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => 'In the uploads folder, we have the following directories:', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'list me all files in astro folder (and subfolders)'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[], []]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => 'In the `astro` folder, we have the following files:'],
                ['role' => 'user', 'content' => 'what is the ICS file download URL?'],
            ],
            'expected' => [
                ['role' => 'user', 'content' => 'what do we have in uploads folder?'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => 'In the uploads folder, we have the following directories:', 'tool_calls' => null],
                ['role' => 'user', 'content' => 'list me all files in astro folder (and subfolders)'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [[], []]],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'tool', 'content' => 'function_result'],
                ['role' => 'assistant', 'content' => 'In the `astro` folder, we have the following files:'],
                ['role' => 'user', 'content' => 'what is the ICS file download URL?'],
            ],
            'maxMessages' => 20,
            'truncateTo' => 10,
        ]];
    }


    // OLD DATA


    public function processDataProvider()
    {
        return [

            // Test Case 2: Truncate to last 7 messages


            // Test Case 6: Many tools calls
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'What is the weather for next 7 days'],
                    ['role' => 'assistant', 'content' => 'Let me check', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcome'],
                ],
                'expected' => [
                    ['role' => 'assistant', 'content' => 'Let me check', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcome'],
                ],
                'maxMessages' => 11,
                'truncateTo' => 5,
            ],

            // Test Case 7: Many tools calls 2 - never break tools
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'What is the weather for next 7 days'],
                    ['role' => 'assistant', 'content' => 'Let me check', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcome'],
                ],
                'expected' => [
                    ['role' => 'assistant', 'content' => 'Let me check', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcome'],
                ],
                'maxMessages' => 8,
                'truncateTo' => 5,
            ],

            // Test Case 8: Truncate to last 5 messages and ensure cutoff doesn't end with user message
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'How are you?'],
                    ['role' => 'assistant', 'content' => 'I am fine, thank you.', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => null],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'What about tomorrow?'],
                    ['role' => 'assistant', 'content' => 'Let me check again.', 'tool_calls' => null],
                ],
                'expected' => [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => null],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'What about tomorrow?'],
                    ['role' => 'assistant', 'content' => 'Let me check again.', 'tool_calls' => null],
                ],
                'maxMessages' => 7,
                'truncateTo' => 5,
            ],

            // Test Case 9: Regular conversation

            // Test Case 10: Multiple function requests
            [
                'messages' => [
                    ['role' => 'assistant', 'content' => 'Hey there! What can I do for you?', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'generate events calendar'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'The events calendar for 2025 has been successfully generated.', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'how many lectures have we imported?'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'We have a total of 70 lectures imported as private posts in the system.', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'cool'],
                    ['role' => 'assistant', 'content' => 'If there is anything else you need, feel free to ask!', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'what do we have in uploads folder?'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'In the uploads folder, we have the following directories:', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'list me all files in astro folder (and subfolders)'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[], []]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'tool', 'content' => 'function_result'],
                ],
                'expected' => [
                    ['role' => 'user', 'content' => 'what do we have in uploads folder?'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'In the uploads folder, we have the following directories:', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'list me all files in astro folder (and subfolders)'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[], []]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'tool', 'content' => 'function_result'],
                ],
                'maxMessages' => 20,
                'truncateTo' => 10,
            ],

            // Test Case 11: Multiple function requests - continuation
            [
                'messages' => [
                    ['role' => 'assistant', 'content' => 'Hey there! What can I do for you?', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'generate events calendar'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'The events calendar for 2025 has been successfully generated.', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'how many lectures have we imported?'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'We have a total of 70 lectures imported as private posts in the system.', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'cool'],
                    ['role' => 'assistant', 'content' => 'If there is anything else you need, feel free to ask!', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'what do we have in uploads folder?'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'In the uploads folder, we have the following directories:', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'list me all files in astro folder (and subfolders)'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[], []]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'In the `astro` folder, we have the following files:'],
                    ['role' => 'user', 'content' => 'what is the ICS file download URL?'],
                ],
                'expected' => [
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'In the uploads folder, we have the following directories:', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'list me all files in astro folder (and subfolders)'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[], []]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => 'In the `astro` folder, we have the following files:'],
                    ['role' => 'user', 'content' => 'what is the ICS file download URL?'],
                ],
                'maxMessages' => 20,
                'truncateTo' => 10,
            ],
        ];
    }
}
