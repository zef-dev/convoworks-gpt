<?php

declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Factory\PackageProviderFactory;
use Convo\Gpt\Admin\SettingsProcessor;
use Convo\Gpt\Admin\SettingsView;
use Convo\Gpt\Tools\CommentRestFunctions;
use Convo\Gpt\Tools\MediaRestFunctions;
use Convo\Gpt\Tools\PagesRestFunctions;
use Convo\Gpt\Tools\PluginRestFunctions;
use Convo\Gpt\Tools\PostRestFunctions;
use Convo\Gpt\Tools\SettingsRestFunctions;
use Convo\Gpt\Tools\TaxonomyRestFunctions;
use Convo\Gpt\Tools\UserRestFunctions;
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

        $posts = new PostRestFunctions();
        $posts->register();

        $pages = new PagesRestFunctions();
        $pages->register();

        $comments = new CommentRestFunctions();
        $comments->register();

        $users = new UserRestFunctions();
        $users->register();

        $media = new MediaRestFunctions();
        $media->register();

        $plugins = new PluginRestFunctions();
        $plugins->register();

        $taxonomies = new TaxonomyRestFunctions();
        $taxonomies->register();

        $settings = new SettingsRestFunctions();
        $settings->register();
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
