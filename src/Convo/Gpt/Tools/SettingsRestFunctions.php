<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class SettingsRestFunctions extends AbstractRestFunctions
{
    public function register()
    {
        add_filter('convo_mcp_register_wp_settings', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'get_settings',
                'description' => 'Retrieve WordPress settings.',
                'parameters' => [],
                'defaults' => [],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'settings')
            ],
            [
                'name' => 'update_settings',
                'description' => 'Update WordPress settings.',
                'parameters' => [
                    'settings' => ['type' => 'array', 'description' => 'Settings key-value pairs']
                ],
                'defaults' => [],
                'required' => ['settings'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('POST', 'settings')($data['settings']);
                }
            ]
        ];
    }
}
