<?php

require_once __DIR__ . '/../../convoworks-core/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Convo\Gpt\Util;

class TruncateToSizeTest extends TestCase
{
    /**
     * @dataProvider dataProviderTruncateToSize
     */
    public function testTruncateToSize($messages, $expected, $size)
    {
        $result = Util::truncateToSize($messages, $size);
        $this->assertEquals(count($expected), count($result), "Failed asserting that the count of messages is equal to expected count.");
        $this->assertEquals($expected, $result, "Failed asserting that two arrays are equal.");
    }

    public function dataProviderTruncateToSize()
    {
        return [
            // #0 Empty array
            [
                [],
                [],
                5
            ],
            // #1 No truncation needed
            [
                [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thnx'],
                ],
                [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Let me check.', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'assistant', 'content' => 'Sunny, 75°F'],
                    ['role' => 'user', 'content' => 'Thnx'],
                ],
                5
            ],
            // #2 Truncate, preserve groupings
            [
                [
                    ['role' => 'assistant', 'content' => 'How can I help?'],
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => 'Sorry, I can\'t help.'],
                    ['role' => 'user', 'content' => 'I thought you can'],
                    ['role' => 'assistant', 'content' => 'Weel, not'],
                    ['role' => 'user', 'content' => 'No prob'],
                ],
                [
                    ['role' => 'user', 'content' => 'I thought you can'],
                    ['role' => 'assistant', 'content' => 'Weel, not'],
                    ['role' => 'user', 'content' => 'No prob'],
                ],
                3
            ],
            // #3 Truncate, keep user/assistant together
            [
                [
                    ['role' => 'user', 'content' => 'Yo!'],
                    ['role' => 'assistant', 'content' => 'How can I help?', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'Hello!'],
                    ['role' => 'assistant', 'content' => 'Hi there!', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'How are you?'],
                ],
                [
                    ['role' => 'user', 'content' => 'Hello!'],
                    ['role' => 'assistant', 'content' => 'Hi there!', 'tool_calls' => null],
                    ['role' => 'user', 'content' => 'How are you?'],
                ],
                3
            ],
            // #4 Truncate, keep tool_calls group together
            [
                [
                    ['role' => 'user', 'content' => 'What is the weather?'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'assistant', 'content' => 'Sunny, 65°F'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcoem'],
                ],
                [
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcoem'],
                ],
                2
            ],

            // #5 Truncate, keep large tool_calls group together
            [
                [
                    ['role' => 'user', 'content' => 'What is the weather for next 7 days'],
                    ['role' => 'assistant', 'content' => 'Let me check', 'tool_calls' => [['name' => 'weather_api_call']]],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 60°F'],
                    ['role' => 'tool', 'content' => 'Cold, 40°F'],
                    ['role' => 'tool', 'content' => 'Rain, 60°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 65°F'],
                    ['role' => 'tool', 'content' => 'Sunny, 75°F'],
                    ['role' => 'assistant', 'content' => 'Weather is great'],
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcome'],
                ],
                [
                    ['role' => 'user', 'content' => 'Thank you.'],
                    ['role' => 'assistant', 'content' => 'You are welcome'],
                ],
                10
            ],
            // #6 Truncate, group size > size (should keep last group only)
            [
                [
                    ['role' => 'user', 'content' => 'A'],
                    ['role' => 'assistant', 'content' => 'B'],
                    ['role' => 'user', 'content' => 'C'],
                    ['role' => 'assistant', 'content' => 'D'],
                    ['role' => 'user', 'content' => 'E'],
                    ['role' => 'assistant', 'content' => 'F'],
                ],
                [
                    ['role' => 'user', 'content' => 'E'],
                    ['role' => 'assistant', 'content' => 'F'],
                ],
                2
            ],
            // #7 Truncate, group size > size (should keep only last group, even if group is larger than size)
            [
                [
                    ['role' => 'user', 'content' => 'A'],
                    ['role' => 'assistant', 'content' => 'B'],
                    ['role' => 'user', 'content' => 'C'],
                    ['role' => 'assistant', 'content' => 'D'],
                    ['role' => 'user', 'content' => 'E'],
                    ['role' => 'assistant', 'content' => 'F'],
                    ['role' => 'user', 'content' => 'G'],
                    ['role' => 'assistant', 'content' => 'H'],
                    ['role' => 'user', 'content' => 'I'],
                    ['role' => 'assistant', 'content' => 'J'],
                ],
                [
                    ['role' => 'user', 'content' => 'I'],
                    ['role' => 'assistant', 'content' => 'J'],
                ],
                2
            ],
            // #8 Truncate, complex tool_calls scenario
            [
                [
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
                [
                    ['role' => 'user', 'content' => 'list me all files in astro folder (and subfolders)'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[]]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[], []]],
                    ['role' => 'tool', 'content' => 'function_result'],
                    ['role' => 'tool', 'content' => 'function_result'],
                ],
                9
            ],
        ];
    }
}
