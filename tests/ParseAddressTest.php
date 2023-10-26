<?php

use PHPUnit\Framework\TestCase;

class ParseAddressTest extends TestCase
{
    
    
    public function setUp(): void
    {
        parent::setUp();
    }
    
    
    public function preprocessDataProvider()
    {
        return [
            [
                '{ "a": "Here is some FILE_APPEND text", "b": FILE_APPEND }',
                '{ "a": "Here is some FILE_APPEND text", "b": "FILE_APPEND" }'
            ],
            [
                '{"constant":CONSTANT_VALUE}',
                '{"constant":"CONSTANT_VALUE"}'
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
                '{"boolean_true": "TRUE", "boolean_false": "FALSE", "null_value": "NULL"}'
            ],
            [
                '{CONSTANT_KEY: "value"}',
                '{"CONSTANT_KEY": "value"}'
            ]
        ];
    }
    
    /**
     * @dataProvider preprocessDataProvider
     */
    public function testPreprocessJson( $inputJson, $expectedJson)
    {
        $result = self::preprocessJsonWithConstants($inputJson);
        $this->assertEquals( $expectedJson, $result, "Failed asserting that two strings are equal.");
    }
    
    
    public static function preprocessJsonWithConstants( $json)
    {
        // GREAT ONE
        return preg_replace_callback('/"([^"]+)"|([A-Z_]+)/', function ($matches) {
            // If it is a constant (not enclosed by quotes), add quotes
            if (!empty($matches[2]) && !preg_match('/"[A-Z_]+"/', $matches[2])) {
                return '"' . $matches[2] . '"';
            }
            // If it is within quotes, return as is
            return $matches[0];
        }, $json);
    }
}