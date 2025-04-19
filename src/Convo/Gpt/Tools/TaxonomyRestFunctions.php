<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class TaxonomyRestFunctions extends AbstractRestFunctions
{
    public function register()
    {
        add_filter('convo_mcp_register_wp_taxonomies', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'list_taxonomies',
                'description' => 'List WordPress taxonomies.',
                'parameters' => [],
                'defaults' => [],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'taxonomies')
            ],
            [
                'name' => 'get_taxonomy',
                'description' => 'Retrieve a taxonomy by slug.',
                'parameters' => [
                    'slug' => ['type' => 'string', 'description' => 'Taxonomy slug']
                ],
                'defaults' => [],
                'required' => ['slug'],
                'execute' => function ($data) {
                    $fn = $this->_makeRestFn('GET', "taxonomies/{$data['slug']}");
                    return $fn($data);
                }
            ]
        ];
    }
}
