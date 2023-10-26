<?php

use PHPUnit\Framework\TestCase;
use Convo\Gpt\Pckg\ChatCompletionV2Element;

class ProcessJsonWithConstantsTest extends TestCase
{
    
    public function processDataProvider()
    {
        define( 'CONSTANT_VALUE', 'My String Value');
        return [
            [
                '{ "a": "Here is some FILE_APPEND text", "b": FILE_APPEND }',
                '{ "a": "Here is some FILE_APPEND text", "b": '.json_encode( constant( "FILE_APPEND")).' }'
            ],
            [
                '{"constant":CONSTANT_VALUE}',
                '{"constant":'.json_encode( constant( "CONSTANT_VALUE")).'}'
            ],
            [
                '["element1", "element2", ELEMENT]',
                '["element1", "element2", "ELEMENT"]'
            ],
            [
                '{"mixed": "Text ELEMENT"}',
                '{"mixed": "Text ELEMENT"}'
            ],
            
            [
                '{"value": CONSTANT, "number": 123}',
                '{"value": "CONSTANT", "number": 123}'
            ],
            [
                '{"object": {"nested_constant": NESTED_CONSTANT}}',
                '{"object": {"nested_constant": "NESTED_CONSTANT"}}'
            ],
            [
                '{"boolean_true": TRUE, "boolean_false": FALSE, "null_value": NULL}',
                '{"boolean_true": true, "boolean_false": false, "null_value": null}'
            ],
            [
                '{CONSTANT_KEY: "value"}',
                '{"CONSTANT_KEY": "value"}'
            ]
        ];
    }
    
    /**
     * @dataProvider processDataProvider
     */
    public function testProcessJson( $inputJson, $expectedData)
    {
        $result = ChatCompletionV2Element::processJsonWithConstants( $inputJson);
        $this->assertEquals( $expectedData, $result, "Failed asserting that two objects are equal.");
    }
    
}