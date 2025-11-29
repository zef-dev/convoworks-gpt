<?php

declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Core\Factory\IPackageDescriptor;
use Convo\Gpt\Admin\McpConvoworksManager;
use Convo\Gpt\Admin\SettingsViewModel;
use Convo\Gpt\Mcp\CommandDispatcher;
use Convo\Gpt\Mcp\McpServerPlatform;
use Convo\Gpt\Mcp\McpSessionManagerFactory;
use Convo\Gpt\Mcp\StreamableRestHandler;
use Convo\Gpt\Mcp\StreamHandler;
use Convo\Gpt\Mcp\StreamWriter;
use Convo\Gpt\Pckg\GptPackageDefinition;
use Psr\Log\LoggerInterface;

class PluginContext
{

    /**
     * @var LoggerInterface
     */
    private $_logger;

    private $_cache = [];

    /**
     * @var string
     */
    private static $_convoWPPluginClass;

    public function __construct() {}

    public function init()
    {
        $this->_logger  =   $this->getContainer()->get('logger');

        // $this->_logger->debug('-------------------------------------------------------------');
        // $this->_logger->debug('Initializing Convoworks MCP Plugin Context');
    }

    /**
     * @return string
     */
    private static function getConvoWPPluginClass()
    {
        if (!isset(self::$_convoWPPluginClass)) {
            if (class_exists('Convo\Wp\Providers\ConvoWPPlugin')) {
                self::$_convoWPPluginClass = 'Convo\Wp\Providers\ConvoWPPlugin';
            } elseif (class_exists('Convo\Providers\ConvoWPPlugin')) {
                self::$_convoWPPluginClass = 'Convo\Providers\ConvoWPPlugin';
            } else {
                throw new \Exception('ConvoWPPlugin class not found. Neither Convo\Wp\Providers\ConvoWPPlugin nor Convo\Providers\ConvoWPPlugin is available.');
            }
        }
        return self::$_convoWPPluginClass;
    }

    /**
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer()
    {
        $convoWPPluginClass = self::getConvoWPPluginClass();

        if (is_admin()) {
            return $convoWPPluginClass::getAdminDiContainer();
        }

        //         if ( wp_doing_cron()) {
        //             $container =   $convoWPPluginClass::getPublicDiContainer();
        //         } else {
        //             $container =   $convoWPPluginClass::getPublicDiContainer();
        //         }

        return $convoWPPluginClass::getPublicDiContainer();
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
            $this->_cache['settingsViewModel'] = new SettingsViewModel($this->getLogger(), $this->getContainer()->get('convoServiceDataProvider'));
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
                    $stream_writer = new StreamWriter($logger);
                    $stream_handler = new StreamHandler($stream_writer, $logger);

                    $mcp_manager_factory = new McpSessionManagerFactory(
                        $logger,
                        CONVO_GPT_MCP_SESSION_STORAGE_PATH
                    );

                    $command_dispatcher = new CommandDispatcher(
                        $container->get('convoServiceFactory'),
                        $container->get('convoServiceParamsFactory'),
                        $logger,
                        $mcp_manager_factory
                    );

                    $handler = new StreamableRestHandler(
                        $logger,
                        $container->get('httpFactory'),
                        $container->get('convoServiceFactory'),
                        $container->get('convoServiceDataProvider'),
                        $mcp_manager_factory,
                        $command_dispatcher,
                        $stream_handler
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
                        $mcp_manager_factory
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
