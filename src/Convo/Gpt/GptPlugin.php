<?php declare(strict_types=1);

namespace Convo\Gpt;

use Convo\Gpt\Pckg\GptPackageDefinition;
use Convo\Core\Factory\IPackageDescriptor;

class GptPlugin
{
    /**
     * @var IPackageDescriptor
     */
    private $_package;
    
    public function __construct()
    {
    }

    public function register()
    {
        add_action( 'init', [ $this, 'init']);
    }
    
    public function init()
    {
        if ( !defined( 'CONVOWP_VERSION')) {
            error_log( 'GPT: Convoworks WP is not present. Exiting ...');
            
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-warning is-dismissible">
      <p><b>Convoworks GPT</b> requires <b>Convoworks WP</b> plugin to be installed and activated</p>
      </div>';
            });
                return;
        }
        
        add_action( 'register_convoworks_package', [$this, 'gptPackageRegister'], 10, 2);
    }
    
    /**
     * @param \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory
     * @param \Psr\Container\ContainerInterface $container
     */
    public function gptPackageRegister( $packageProviderFactory, $container) {
        $packageProviderFactory->registerPackage( $this->getGptPackage( $container));
    }
    
    public function getGptPackage( $container)
    {
        if ( !isset( $this->_package))
        {
            $this->_package = new \Convo\Core\Factory\FunctionPackageDescriptor(
                '\Convo\Gpt\Pckg\GptPackageDefinition',
                function() use ( $container) {
                    $logger = $container->get( 'logger');
                    $logger->debug( 'Registering package ['.GptPackageDefinition::NAMESPACE.']');
                    return new GptPackageDefinition(
                        $logger, $container->get('packageProviderFactory'), new GptApiFactory( $logger, $container->get('httpFactory')));
                });
        }
        
        return $this->_package;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }
}
