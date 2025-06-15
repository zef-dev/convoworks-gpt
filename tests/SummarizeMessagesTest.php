<?php

use PHPUnit\Framework\TestCase;
use Convo\Gpt\Util;

class SummarizeMessagesTest extends TestCase
{

    /**
     * @dataProvider shouldHandleWHenContentIsNull
     */
    public function testSummarizeMessages($messages, $expected)
    {
        $result = Util::serializeGptMessages($messages);
        $this->assertEquals($expected, $result, "Failed asserting that serialized messages are as expected.");
    }

    public function shouldHandleWHenContentIsNull()
    {
        return [
            [
                'messages' => [
                    [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            [
                                'id' => 'call_glNresze0Hjxix79HKUOKTPi',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'email_verify',
                                    'arguments' => '{"email":"tole@zefdev.com","code":7921}',
                                ],
                            ],
                        ],
                        'refusal' => '',
                    ],
                    [
                        'role' => 'tool',
                        'tool_call_id' => 'call_glNresze0Hjxix79HKUOKTPi',
                        'content' => '{"verified" : true}',
                    ],
                    [
                        'role' => 'assistant',
                        'content' => 'Your email tole@zefdev.com has been successfully verified. Now, let\'s schedule your demo. Could you please tell me your preferred date and time for the demo? Remember, demos can be scheduled between 9:00 AM and 4:30 PM during the workweek, and we need to avoid same-day or next-day bookings.',
                        'refusal' => '',
                    ],
                ],
                'expected' => "Assistant: Your email tole@zefdev.com has been successfully verified. Now, let's schedule your demo. Could you please tell me your preferred date and time for the demo? Remember, demos can be scheduled between 9:00 AM and 4:30 PM during the workweek, and we need to avoid same-day or next-day bookings.",
            ],
        ];
    }
}
