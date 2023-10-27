<?php

use PHPUnit\Framework\TestCase;
use Convo\Gpt\Pckg\ChatCompletionV2Element;

class ProcessJsonWithConstantsTest extends TestCase
{
    
    public function processDataProvider()
    {
        define( 'CONSTANT_VALUE', 'My String Value');
        define( 'ELEMENT', 'ELEMENT String Value');
        define( 'NESTED_CONSTANT', 'NESTED_CONSTANT String Value');
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
                '["element1", "element2", '.json_encode( constant( "ELEMENT")).']'
            ],
            [
                '{"mixed": "Text ELEMENT"}',
                '{"mixed": "Text ELEMENT"}'
            ],
            
            [
                '{"value": NOT_CONSTANT, "number": 123}',
                '{"value": NOT_CONSTANT, "number": 123}'
            ],
            [
                '{"object": {"nested_constant": NESTED_CONSTANT}}',
                '{"object": {"nested_constant": '.json_encode( constant( "NESTED_CONSTANT")).'}}'
            ],
            [
                '{"boolean_true": TRUE, "boolean_false": FALSE, "null_value": NULL}',
                '{"boolean_true": true, "boolean_false": false, "null_value": null}'
            ],
            [
                '[\n    \"C:\\\\xampp\\\\htdocs\\\\wp-test\\\\.htaccess\",\n    \"# BEGIN WordPress\\n# The directives (lines) between \\\"BEGIN WordPress\\\" and \\\"END WordPress\\\" are\\n# dynamically generated, and should only be modified via WordPress filters.\\n# Any changes to the directives between these markers will be overwritten.\\n<IfModule mod_rewrite.c>\\nRewriteEngine On\\nRewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\\nRewriteBase \/wp-test\/\\nRewriteRule ^index\\\\.php$ - [L]\\nRewriteCond %{REQUEST_FILENAME} !-f\\nRewriteCond %{REQUEST_FILENAME} !-d\\nRewriteRule . \/wp-test\/index.php [L]\\n<\/IfModule>\\n# END WordPress\\n\\n# BEGIN Restrict access to debug.log\\n<Files debug.log>\\nOrder allow,deny\\nDeny from all\\n<\/Files>\\n# END Restrict access to debug.log\\n\"\n  ]',
                '[\n    \"C:\\\\xampp\\\\htdocs\\\\wp-test\\\\.htaccess\",\n    \"# BEGIN WordPress\\n# The directives (lines) between \\\"BEGIN WordPress\\\" and \\\"END WordPress\\\" are\\n# dynamically generated, and should only be modified via WordPress filters.\\n# Any changes to the directives between these markers will be overwritten.\\n<IfModule mod_rewrite.c>\\nRewriteEngine On\\nRewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\\nRewriteBase \/wp-test\/\\nRewriteRule ^index\\\\.php$ - [L]\\nRewriteCond %{REQUEST_FILENAME} !-f\\nRewriteCond %{REQUEST_FILENAME} !-d\\nRewriteRule . \/wp-test\/index.php [L]\\n<\/IfModule>\\n# END WordPress\\n\\n# BEGIN Restrict access to debug.log\\n<Files debug.log>\\nOrder allow,deny\\nDeny from all\\n<\/Files>\\n# END Restrict access to debug.log\\n\"\n  ]'
            ]
        ];
    }
    
    /**
     * @dataProvider processDataProvider
     */
    public function testProcessJson( $inputJson, $expectedJson)
    {
        $result = ChatCompletionV2Element::processJsonWithConstants( $inputJson);
        
        $this->assertEquals( $expectedJson, $result, "Failed asserting that two objects are equal.");
    }
    
}