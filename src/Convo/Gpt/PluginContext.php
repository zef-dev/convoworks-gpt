<?php

declare(strict_types=1);

namespace Convo\Gpt;



use Convo\Providers\ConvoWPPlugin;
use Convo\Core\Factory\IPackageDescriptor;
use Convo\Gpt\Admin\McpConvoworksManager;
use Convo\Gpt\Admin\SettingsViewModel;
use Convo\Gpt\Mcp\McpFilesystemSessionStore;
use Convo\Gpt\Mcp\McpServerPlatform;
use Convo\Gpt\Mcp\McpSessionManager;
use Convo\Gpt\Mcp\SSERestHandler;
use Convo\Gpt\Pckg\GptPackageDefinition;
use Psr\Log\LoggerInterface;

class PluginContext
{

    /**
     * @var LoggerInterface
     */
    private $_logger;

    private $_cache = [];

    public function __construct() {}

    public function init()
    {
        $this->_logger  =   $this->getContainer()->get('logger');

        $this->_logger->debug('-------------------------------------------------------------');
        $this->_logger->debug('Initializing Convoworks MCP Plugin Context');
    }

    /**
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer()
    {
        if (is_admin()) {
            return ConvoWPPlugin::getAdminDiContainer();
        }

        //         if ( wp_doing_cron()) {
        //             $container =   ConvoWPPlugin::getPublicDiContainer();
        //         } else {
        //             $container =   ConvoWPPlugin::getPublicDiContainer();
        //         }

        return ConvoWPPlugin::getPublicDiContainer();;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    public function getSettingsViewModel()
    {
        if (!isset($this->_cache['settingsViewModel'])) {
            $this->_cache['settingsViewModel'] = new SettingsViewModel($this->getLogger());
            $this->_cache['settingsViewModel']->init();
        }

        return $this->_cache['settingsViewModel'];
    }

    /**
     * @return McpConvoworksManager
     */
    public function getMcpConvoworksManager()
    {
        if (!isset($this->_cache['mcpConvoworksManager'])) {
            $this->_cache['mcpConvoworksManager'] = new McpConvoworksManager(
                $this->getLogger(),
                $this->getMcpServerPackage(),
                $this->getContainer()->get('convoServiceDataProvider')
            );
        }

        return $this->_cache['mcpConvoworksManager'];
    }

    /**
     * @return IPackageDescriptor
     */
    public function getMcpServerPackage()
    {
        if (!isset($this->_cache['mcpPackage'])) {
            $container = $this->getContainer();
            $this->_cache['mcpPackage'] = new \Convo\Core\Factory\FunctionPackageDescriptor(
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
                        $mcp_platform,
                        $mcp_manager
                    );
                }
            );
        }

        return $this->_cache['mcpPackage'];
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
