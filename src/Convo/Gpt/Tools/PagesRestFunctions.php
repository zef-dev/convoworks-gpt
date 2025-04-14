<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class PagesRestFunctions extends AbstractRestFunctions
{
    public function register()
    {
        add_filter('convo_mcp_register_wp_pages', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'list_pages',
                'description' => 'List WordPress pages with filters.',
                'parameters' => [
                    'status' => ['type' => 'string', 'description' => 'Page status', 'default' => 'publish'],
                    'per_page' => ['type' => 'number', 'description' => 'Pages per page', 'default' => 10],
                    'page' => ['type' => 'number', 'description' => 'Page number', 'default' => 1]
                ],
                'defaults' => ['status' => 'publish', 'per_page' => 10, 'page' => 1],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'pages')
            ],
            [
                'name' => 'get_page',
                'description' => 'Retrieve a page by ID.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Page ID']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('GET', "pages/{$data['id']}")($data);
                }
            ],
            [
                'name' => 'create_page',
                'description' => 'Create a new page.',
                'parameters' => [
                    'title' => ['type' => 'string', 'description' => 'Page title'],
                    'content' => ['type' => 'string', 'description' => 'Page content'],
                    'status' => ['type' => 'string', 'enum' => ['publish', 'draft'], 'default' => 'draft']
                ],
                'defaults' => ['status' => 'draft'],
                'required' => ['title', 'content'],
                'execute' => $this->_makeRestFn('POST', 'pages')
            ],
            [
                'name' => 'update_page',
                'description' => 'Update an existing page.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Page ID'],
                    'title' => ['type' => 'string'],
                    'content' => ['type' => 'string']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $id = $data['id'];
                    unset($data['id']);
                    return $this->_makeRestFn('POST', "pages/{$id}")($data);
                }
            ],
            [
                'name' => 'delete_page',
                'description' => 'Delete a page.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Page ID'],
                    'force' => ['type' => 'boolean', 'default' => true]
                ],
                'defaults' => ['force' => true],
                'required' => ['id'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('DELETE', "pages/{$data['id']}")(['force' => $data['force']]);
                }
            ]
        ];
    }
}
