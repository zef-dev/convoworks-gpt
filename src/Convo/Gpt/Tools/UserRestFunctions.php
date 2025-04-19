<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class UserRestFunctions extends AbstractRestFunctions
{
    public function register()
    {
        add_filter('convo_mcp_register_wp_users', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'list_users',
                'description' => 'List WordPress users with filters.',
                'parameters' => [
                    'role' => ['type' => 'string', 'description' => 'User role', 'default' => 'subscriber'],
                    'per_page' => ['type' => 'number', 'description' => 'Users per page', 'default' => 10],
                    'page' => ['type' => 'number', 'description' => 'Page number', 'default' => 1]
                ],
                'defaults' => ['role' => 'subscriber', 'per_page' => 10, 'page' => 1],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'users')
            ],
            [
                'name' => 'get_user',
                'description' => 'Retrieve a user by ID.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'User ID']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $fn = $this->_makeRestFn('GET', "users/{$data['id']}");
                    return $fn($data);
                }
            ],
            [
                'name' => 'create_user',
                'description' => 'Create a new user.',
                'parameters' => [
                    'username' => ['type' => 'string', 'description' => 'Username'],
                    'email' => ['type' => 'string', 'description' => 'User email'],
                    'password' => ['type' => 'string', 'description' => 'User password'],
                    'role' => ['type' => 'string', 'default' => 'subscriber']
                ],
                'defaults' => ['role' => 'subscriber'],
                'required' => ['username', 'email', 'password'],
                'execute' => $this->_makeRestFn('POST', 'users')
            ],
            [
                'name' => 'update_user',
                'description' => 'Update an existing user.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'User ID'],
                    'email' => ['type' => 'string'],
                    'role' => ['type' => 'string']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $id = $data['id'];
                    unset($data['id']);
                    $fn = $this->_makeRestFn('POST', "users/{$id}");
                    return $fn($data);
                }
            ],
            [
                'name' => 'delete_user',
                'description' => 'Delete a user.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'User ID'],
                    'reassign' => ['type' => 'number', 'description' => 'Reassign posts to this user ID']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('DELETE', "users/{$data['id']}")(['reassign' => $data['reassign']]);
                }
            ]
        ];
    }
}
