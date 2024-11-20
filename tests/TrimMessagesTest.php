<?php

use PHPUnit\Framework\TestCase;
use Convo\Gpt\Util;

class TrimMessagesTest extends TestCase
{
    public function processDataProvider()
    {
        return [
            // Test Case 1: No truncation needed
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ],
                'expected' => [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                ],
                'maxMessages' => 4,
                'truncateTo' => 3,
            ],

            // Test Case 2: Truncate to last 7 messages
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'How are you?'],
                    ['role' => 'assistant', 'content' => 'I am fine, thank you.'],
                    ['role' => 'user', 'content' => 'What is the weather for 2 days?'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'What about tomorrow?'],
                    ['role' => 'assistant', 'content' => 'Let me check again.'],
                    ['role' => 'user', 'content' => 'Thanks'],
                ],
                'expected' => [
                    ['role' => 'user', 'content' => 'What is the weather for 2 days?'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'What about tomorrow?'],
                    ['role' => 'assistant', 'content' => 'Let me check again.'],
                    ['role' => 'user', 'content' => 'Thanks'],
                ],
                'maxMessages' => 8,
                'truncateTo' => 7,
            ],

            // Test Case 3: Preserve tool_calls and tools pair
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                ],
                'expected' => [
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                ],
                'maxMessages' => 5,
                'truncateTo' => 3,
            ],

            // Test Case 5: Empty input messages
            [
                'messages' => [],
                'expected' => [],
                'maxMessages' => 5,
                'truncateTo' => 3,
            ],

            // Test Case 6: Keep grouped user & assistant
            [
                'messages' => [
                    ['role' => 'assistant', 'content' => 'How can I help?'],
                    ['role' => 'user', 'content' => 'Hello!'],
                    ['role' => 'assistant', 'content' => 'Hi there!'],
                    ['role' => 'user', 'content' => 'How are you?'],
                ],
                'expected' => [
                    ['role' => 'user', 'content' => 'Hello!'],
                    ['role' => 'assistant', 'content' => 'Hi there!'],
                    ['role' => 'user', 'content' => 'How are you?'],
                ],
                'maxMessages' => 3,
                'truncateTo' => 2,
            ],

            // Test Case 7: Many tools calls
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'What is the weather for next 7 days'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are wlcome'],
                ],
                'expected' => [
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are wlcome'],
                ],
                'maxMessages' => 11,
                'truncateTo' => 5,
            ],

            // Test Case 7: Many tools calls 2 - never break tools
            [
                'messages' => [
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are wlcome'],
                ],
                'expected' => [
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are wlcome'],
                ],
                'maxMessages' => 8,
                'truncateTo' => 5,
            ],


            // Test Case 8: Truncate to last 5 messages and make sure cuto off part does not end with user message
            [
                'messages' => [
                    ['role' => 'user', 'content' => 'How are you?'],
                    ['role' => 'assistant', 'content' => 'I am fine, thank you.'],
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'What about tomorrow?'],
                    ['role' => 'assistant', 'content' => 'Let me check again.'],
                ],
                'expected' => [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.'],
                    ['role' => 'tool_calls', 'content' => 'weather_api_call'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'What about tomorrow?'],
                    ['role' => 'assistant', 'content' => 'Let me check again.'],
                ],
                'maxMessages' => 7,
                'truncateTo' => 5,
            ],
        ];
    }


    /**
     * @dataProvider processDataProvider
     */
    public function testTruncateMessages($messages, $expected, $maxMessages, $truncateTo)
    {
        $result = Util::truncate($messages, $maxMessages, $truncateTo);

        $this->assertEquals($expected, $result, "Failed asserting that two arrays are equal.");
    }
}
