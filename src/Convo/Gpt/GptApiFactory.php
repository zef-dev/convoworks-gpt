<?php declare(strict_types=1);

namespace Convo\Gpt;


use Convo\Core\Util\IHttpFactory;

class GptApiFactory
{
    
    /**
     * @var IHttpFactory
     */
    private $_httpFactory;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    
    public function __construct( $logger, $httpFactory)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;
    }

    public function getApi( $apiKey)
    {
        return new GptApi( $this->_logger, $this->_httpFactory, $apiKey);
    }
    
    
    // UTIL
    public function __toString()
    {
        return get_class( $this).'[]';
    }


}
