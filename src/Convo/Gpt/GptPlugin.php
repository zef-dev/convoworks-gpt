<?php

declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Gpt\Pckg\GptPackageDefinition;
use Convo\Core\Factory\IPackageDescriptor;
use Convo\Gpt\Mcp\McpServerPlatform;
use Convo\Gpt\Mcp\McpSessionManager;
use Convo\Gpt\Mcp\McpFilesystemSessionStore;
use Convo\Gpt\Mcp\SSERestHandler;

class GptPlugin
{
    /**
     * @var IPackageDescriptor
     */
    private $_package;

    public function __construct() {}

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

        add_action('register_convoworks_package', [$this, 'gptPackageRegister'], 10, 2);
    }

    /**
     * @param \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory
     * @param \Psr\Container\ContainerInterface $container
     */
    public function gptPackageRegister($packageProviderFactory, $container)
    {
        $packageProviderFactory->registerPackage($this->getGptPackage($container));
    }

    public function getGptPackage($container)
    {
        if (!isset($this->_package)) {
            $this->_package = new \Convo\Core\Factory\FunctionPackageDescriptor(
                '\Convo\Gpt\Pckg\GptPackageDefinition',
                function () use ($container) {
                    $logger = $container->get('logger');
                    $api_factory = new GptApiFactory($logger, $container->get('httpFactory'));
                    $mcp_store = new McpFilesystemSessionStore($logger);
                    $mcp_manager = new McpSessionManager($logger, $mcp_store);
                    $handler = new SSERestHandler(
                        $logger,
                        $container->get('httpFactory'),
                        $container->get('convoServiceFactory'),
                        $container->get('convoServiceParamsFactory'),
                        $container->get('convoServiceDataProvider'),
                        $container->get('eventDispatcher'),
                        $mcp_manager
                    );
                    $mcp_platform = new McpServerPlatform(
                        $logger,
                        $container->get('convoServiceDataProvider'),
                        $container->get('serviceReleaseManager'),
                        $handler
                    );
                    $logger->debug('Registering package [' . GptPackageDefinition::NAMESPACE . ']');
                    return new GptPackageDefinition(
                        $logger,
                        $api_factory,
                        $mcp_platform
                    );
                }
            );
        }

        return $this->_package;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
