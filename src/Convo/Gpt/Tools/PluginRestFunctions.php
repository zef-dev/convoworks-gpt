<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class PluginRestFunctions extends AbstractRestFunctions
{
    public function register()
    {
        add_filter('convo_mcp_register_wp_plugins', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'list_plugins',
                'description' => 'List installed WordPress plugins.',
                'parameters' => [],
                'defaults' => [],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'plugins')
            ],
            [
                'name' => 'activate_plugin',
                'description' => 'Activate a plugin by slug.',
                'parameters' => [
                    'slug' => ['type' => 'string', 'description' => 'Plugin slug']
                ],
                'defaults' => [],
                'required' => ['slug'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('POST', "plugins/{$data['slug']}/activate")([]);
                }
            ],
            [
                'name' => 'deactivate_plugin',
                'description' => 'Deactivate a plugin by slug.',
                'parameters' => [
                    'slug' => ['type' => 'string', 'description' => 'Plugin slug']
                ],
                'defaults' => [],
                'required' => ['slug'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('POST', "plugins/{$data['slug']}/deactivate")([]);
                }
            ]
        ];
    }
}
