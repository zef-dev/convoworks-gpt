<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class CommentRestFunctions extends AbstractRestFunctions
{
    public function register()
    {
        add_filter('convo_mcp_register_wp_comments', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'list_comments',
                'description' => 'List WordPress comments with filters.',
                'parameters' => [
                    'post' => ['type' => 'number', 'description' => 'Post ID'],
                    'per_page' => ['type' => 'number', 'description' => 'Comments per page', 'default' => 10],
                    'page' => ['type' => 'number', 'description' => 'Page number', 'default' => 1]
                ],
                'defaults' => ['per_page' => 10, 'page' => 1],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'comments')
            ],
            [
                'name' => 'get_comment',
                'description' => 'Retrieve a comment by ID.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Comment ID']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $fn = $this->_makeRestFn('GET', "comments/{$data['id']}");
                    return $fn($data);
                }
            ],
            [
                'name' => 'create_comment',
                'description' => 'Create a new comment.',
                'parameters' => [
                    'post' => ['type' => 'number', 'description' => 'Post ID'],
                    'content' => ['type' => 'string', 'description' => 'Comment content'],
                    'author' => ['type' => 'string', 'description' => 'Author name'],
                    'author_email' => ['type' => 'string', 'description' => 'Author email']
                ],
                'defaults' => [],
                'required' => ['post', 'content', 'author', 'author_email'],
                'execute' => $this->_makeRestFn('POST', 'comments')
            ],
            [
                'name' => 'update_comment',
                'description' => 'Update an existing comment.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Comment ID'],
                    'content' => ['type' => 'string']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $id = $data['id'];
                    unset($data['id']);
                    $fn = $this->_makeRestFn('POST', "comments/{$id}");
                    return $fn($data);
                }
            ],
            [
                'name' => 'delete_comment',
                'description' => 'Delete a comment.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Comment ID'],
                    'force' => ['type' => 'boolean', 'default' => true]
                ],
                'defaults' => ['force' => true],
                'required' => ['id'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('DELETE', "comments/{$data['id']}")(['force' => $data['force']]);
                }
            ]
        ];
    }
}
