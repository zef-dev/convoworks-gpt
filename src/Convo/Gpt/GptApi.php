<?php declare(strict_types=1);

namespace Convo\Gpt;


use Convo\Core\Util\IHttpFactory;

class GptApi
{
    private $_apiKey;
    
    /**
     * @var IHttpFactory
     */
    private $_httpFactory;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    
    public function __construct( $logger, $httpFactory, $apiKey)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;
        $this->_apiKey = $apiKey;
    }

    public function completion( $data)
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->_apiKey,
        ];
        
//         $data = [
//             'model' => 'text-davinci-003',
//             'prompt' => $prompt,
//             'temperature' => 0.7,
//             'max_tokens' => 256,
//             'top_p' => 1,
//             'frequency_penalty' => 0,
//             'presence_penalty' => 0,
//         ];
        
        $this->_logger->debug( 'Http request data ['.print_r( $data, true).']');
        
        $config = [];
        
        $client = $this->_httpFactory->getHttpClient( $config);
        $request = $this->_httpFactory->buildRequest( IHttpFactory::METHOD_POST, 'https://api.openai.com/v1/completions', $headers, $data);
        
        $response = $client->sendRequest( $request);
        
        $response_data = json_decode( $response->getBody()->getContents(), true);
        
        $this->_logger->debug( 'Http response data ['.print_r( $response_data, true).']');
        
        return $response_data;
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
