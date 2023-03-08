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
    
    public function __construct( $httpFactory, $apiKey)
    {
        $this->_httpFactory = $httpFactory;
        $this->_apiKey = $apiKey;
    }

    public function completion( $prompt)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->_apiKey,
        ];
        
        $data = [
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'temperature' => 0.7,
            'max_tokens' => 256,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ];
        
        $config = [];
        
        $client = $this->_httpFactory->getHttpClient( $config);
        $request = $this->_httpFactory->buildRequest( 'POST', 'https://api.openai.com/v1/completions', $headers, $data);
        
        $response = $client->sendRequest( $request);
        
        return json_decode( $response->getBody(), true);
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
