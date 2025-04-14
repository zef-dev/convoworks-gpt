<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class PostRestFunctions extends AbstractRestFunctions
{

    public function register()
    {
        add_filter('convo_mcp_register_wp_posts', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'list_posts',
                'description' => 'List WordPress posts with filters.',
                'parameters' => [
                    'status' => ['type' => 'string', 'description' => 'Post status', 'default' => 'publish'],
                    'per_page' => ['type' => 'number', 'description' => 'Posts per page', 'default' => 10],
                    'page' => ['type' => 'number', 'description' => 'Page number', 'default' => 1]
                ],
                'defaults' => ['status' => 'publish', 'per_page' => 10, 'page' => 1],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'posts')
            ],
            [
                'name' => 'get_post',
                'description' => 'Retrieve a post by ID.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Post ID']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $fn = $this->_makeRestFn('GET', "posts/{$data['id']}");
                    return $fn($data);
                }
            ],
            [
                'name' => 'create_post',
                'description' => 'Create a new post.',
                'parameters' => [
                    'title' => ['type' => 'string', 'description' => 'Post title'],
                    'content' => ['type' => 'string', 'description' => 'Post content'],
                    'status' => ['type' => 'string', 'enum' => ['publish', 'draft'], 'default' => 'draft']
                ],
                'defaults' => ['status' => 'draft'],
                'required' => ['title', 'content'],
                'execute' => $this->_makeRestFn('POST', 'posts')
            ],
            [
                'name' => 'update_post',
                'description' => 'Update an existing post.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Post ID'],
                    'title' => ['type' => 'string'],
                    'content' => ['type' => 'string']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $id = $data['id'];
                    unset($data['id']);
                    $fn = $this->_makeRestFn('GET', "posts/{$data['id']}");
                    return $fn($data);
                }
            ],
            [
                'name' => 'delete_post',
                'description' => 'Delete a post.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Post ID'],
                    'force' => ['type' => 'boolean', 'default' => true]
                ],
                'defaults' => ['force' => true],
                'required' => ['id'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('DELETE', "posts/{$data['id']}")(['force' => $data['force']]);
                }
            ],
            [
                'name' => 'search_posts',
                'description' => 'Search posts by title or content.',
                'parameters' => [
                    'search' => ['type' => 'string', 'description' => 'Search query'],
                    'per_page' => ['type' => 'number', 'default' => 5]
                ],
                'defaults' => ['per_page' => 5],
                'required' => ['search'],
                'execute' => $this->_makeRestFn('GET', 'posts')
            ]
        ];
    }
}
