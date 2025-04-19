<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

use Convo\Core\Workflow\IServiceWorkflowComponent;

class MediaRestFunctions extends AbstractRestFunctions
{
    public function register()
    {
        add_filter('convo_mcp_register_wp_media', function ($functions, IServiceWorkflowComponent $elem) {
            $functions = array_merge($functions, $this->_buildFunctions());
            return $functions;
        }, 10, 2);
    }

    private function _buildFunctions()
    {
        return [
            [
                'name' => 'list_media',
                'description' => 'List WordPress media items with filters.',
                'parameters' => [
                    'per_page' => ['type' => 'number', 'description' => 'Media items per page', 'default' => 10],
                    'page' => ['type' => 'number', 'description' => 'Page number', 'default' => 1]
                ],
                'defaults' => ['per_page' => 10, 'page' => 1],
                'required' => [],
                'execute' => $this->_makeRestFn('GET', 'media')
            ],
            [
                'name' => 'get_media',
                'description' => 'Retrieve a media item by ID.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Media ID']
                ],
                'defaults' => [],
                'required' => ['id'],
                'execute' => function ($data) {
                    $fn = $this->_makeRestFn('GET', "media/{$data['id']}");
                    return $fn($data);
                }
            ],
            [
                'name' => 'create_media',
                'description' => 'Upload a new media item.',
                'parameters' => [
                    'file' => ['type' => 'string', 'description' => 'File path or URL'],
                    'title' => ['type' => 'string', 'description' => 'Media title']
                ],
                'defaults' => [],
                'required' => ['file'],
                'execute' => $this->_makeRestFn('POST', 'media')
            ],
            [
                'name' => 'delete_media',
                'description' => 'Delete a media item.',
                'parameters' => [
                    'id' => ['type' => 'number', 'description' => 'Media ID'],
                    'force' => ['type' => 'boolean', 'default' => true]
                ],
                'defaults' => ['force' => true],
                'required' => ['id'],
                'execute' => function ($data) {
                    return $this->_makeRestFn('DELETE', "media/{$data['id']}")(['force' => $data['force']]);
                }
            ]
        ];
    }
}
