<?php declare(strict_types=1);

namespace Convo\Gpt;


use Convo\Core\Util\IHttpFactory;

class GptApi
{
    
    /**
     * @var IHttpFactory
     */
    private $_httpFactory;
    
    public function __construct()
    {
    }

    public function completion( $prompt)
    {
        $response = '';
        return $response;
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
