<?php

declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Factory\IPackageDescriptor;
use Convo\Core\Factory\PackageProviderFactory;
use Convo\Core\Workflow\IServiceWorkflowComponent;
use Convo\Gpt\Admin\SettingsProcessor;
use Convo\Gpt\Admin\SettingsView;
use Psr\Container\ContainerInterface;

class GptPlugin
{

    /**
     * @var PluginContext
     */
    private $_pluginContext;

    public function __construct() {}

    public function getPluginContext()
    {
        if (!isset($this->_pluginContext)) {
            throw new \Exception('Not properly iinitilaized');
        }
        return $this->_pluginContext;
    }

    public function register()
    {
        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        if (!defined('CONVOWP_VERSION')) {
            error_log('GPT: Convoworks WP is not present. Exiting ...');

            add_action('admin_notices', function () {
                echo '<div class="notice notice-warning is-dismissible">
      <p><b>Convoworks GPT</b> requires <b>Convoworks WP</b> plugin to be installed and activated</p>
      </div>';
            });
            return;
        }

        add_action('admin_init', [$this, 'adminInit']);

        add_action('admin_menu', function () {
            $context = $this->getPluginContext();
            $settings_view  =   new SettingsView(
                $context->getLogger(),
                $context->getSettingsViewModel(),
                $context->getMcpConvoworksManager()
            );
            $settings_view->register();
        }, 20);
        $this->_pluginContext   =   new PluginContext();
        $this->_pluginContext->init();

        add_action('register_convoworks_package', [$this, 'gptPackageRegister'], 10, 2);

        add_filter('convo_mcp_register_wp_posts', function ($functions, IServiceWorkflowComponent $elem) {
            $functions[] = $this->_buildPostsFunctions();
            return $functions;
        }, 10, 2);
    }


    private function _buildPostsFunctions()
    {
        $function = [];

        $function['name'] = 'create_post';
        $function['description'] = 'Creates a new WordPress post using the REST API.';

        $function['parameters'] = [
            'title' => [
                'type' => 'string',
                'description' => 'Post title'
            ],
            'content' => [
                'type' => 'string',
                'description' => 'Post content'
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['publish', 'future', 'draft', 'pending', 'private'],
                'description' => 'Post status'
            ],
            'excerpt' => [
                'type' => 'string',
                'description' => 'Post excerpt'
            ],
            'author' => [
                'type' => 'number',
                'description' => 'Author ID'
            ],
            'categories' => [
                'type' => 'array',
                'items' => ['type' => 'number'],
                'description' => 'Array of category IDs'
            ],
            'tags' => [
                'type' => 'array',
                'items' => ['type' => 'number'],
                'description' => 'Array of tag IDs'
            ],
            'featured_media' => [
                'type' => 'number',
                'description' => 'Featured image ID'
            ],
            'format' => [
                'type' => 'string',
                'enum' => ['standard', 'aside', 'chat', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio'],
                'description' => 'Post format'
            ],
            'slug' => [
                'type' => 'string',
                'description' => 'Post slug'
            ]
        ];

        $function['defaults'] = [
            'status' => 'draft'
        ];

        $function['required'] = ['title', 'content'];

        $function['execute'] = function ($data) {
            $data = array_merge(['status' => 'draft'], $data);
            $request = new \WP_REST_Request('POST', '/wp/v2/posts');
            $request->set_body_params($data);
            $response = rest_do_request($request);
            $result = $response->get_data();
            return is_string($result) ? $result : json_encode($result);
        };

        return $function;
    }


    public function adminInit()
    {
        $context    =   $this->getPluginContext();
        $logger     =   $context->getLogger();

        $logger->debug('Admin init ...');

        if (!empty($_POST) && isset($_REQUEST['action'])) {
            $logger->debug('Checking should process ...');
            $processor = new SettingsProcessor($logger, $context->getSettingsViewModel(), $context->getMcpConvoworksManager());
            if ($processor->accepts()) {
                $processor->run();
            }
        }
    }

    /**
     * @param PackageProviderFactory $packageProviderFactory
     * @param ContainerInterface $container
     */
    public function gptPackageRegister($packageProviderFactory, $container)
    {
        $packageProviderFactory->registerPackage($this->getPluginContext()->getMcpServerPackage());
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
