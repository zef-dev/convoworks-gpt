<?php declare(strict_types=1);

namespace Convo\Gpt;


use Convo\Core\Util\IHttpFactory;

class GptApiFactory
{
    
    /**
     * @var IHttpFactory
     */
    private $_httpFactory;
    
    public function __construct()
    {
    }

    public function getApi( $apiKey)
    {
        return new GptApi( $this->_httpFactory, $apiKey);
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
