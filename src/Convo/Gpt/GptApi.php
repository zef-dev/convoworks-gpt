<?php declare(strict_types=1);

namespace Convo\Gpt;


use Convo\Core\Util\IHttpFactory;
use Convo\Core\Util\HttpClientException;

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

    /**
     * @param array $data
     * @return mixed
     * @deprecated
     */
    public function completion( $data)
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->_apiKey,
        ];

        $this->_logger->debug( 'Http request data ['.print_r( $data, true).']');

        $config = [];

        $client = $this->_httpFactory->getHttpClient( $config);
        $request = $this->_httpFactory->buildRequest( IHttpFactory::METHOD_POST, 'https://api.openai.com/v1/completions', $headers, $data);

        $response = $client->sendRequest( $request);

        $response_raw = $response->getBody()->getContents();

        $response_data = json_decode( $response_raw, true);

        $this->_logger->debug( 'Http response data ['.print_r( $response_data, true).']');

        return $response_data;
    }

    public function chatCompletion( $data)
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->_apiKey,
        ];

        $this->_logger->debug( 'Http request data ['.print_r( $data, true).']');

        $config = [];

        $client = $this->_httpFactory->getHttpClient( $config);
        $request = $this->_httpFactory->buildRequest( IHttpFactory::METHOD_POST, 'https://api.openai.com/v1/chat/completions', $headers, $data);

        try {
            $response = $client->sendRequest( $request);
        } catch ( HttpClientException $e) {
            if ( $e->getCode() === 400) {
                $response_data = json_decode( $e->getMessage(), true);
                if ( isset( $response_data['error']['code']) && $response_data['error']['code'] === 'context_length_exceeded') {
                    throw new ContextLengthExceededException( $response_data['error']['message'], 0, $e);
                }
            }
            throw $e;
        }

        $response_data = json_decode( $response->getBody()->getContents(), true);

        $this->_logger->debug( 'Http response data ['.print_r( $response_data, true).']');

        return $response_data;
    }


    public function embeddings( $data)
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->_apiKey,
        ];

        $this->_logger->debug( 'Http request data ['.print_r( $data, true).']');

        $config = [];

        $client = $this->_httpFactory->getHttpClient( $config);
        $request = $this->_httpFactory->buildRequest( IHttpFactory::METHOD_POST, 'https://api.openai.com/v1/embeddings', $headers, $data);

        $response = $client->sendRequest( $request);

        $response_data = json_decode( $response->getBody()->getContents(), true);

      //  $this->_logger->debug( 'Http response data ['.print_r( $response_data, true).']');

        return $response_data;
    }

    public function moderations( $data)
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->_apiKey,
        ];

        $this->_logger->debug( 'Http request data ['.print_r( $data, true).']');

        $config = [];

        $client = $this->_httpFactory->getHttpClient( $config);
        $request = $this->_httpFactory->buildRequest( IHttpFactory::METHOD_POST, 'https://api.openai.com/v1/moderations', $headers, $data);

        $response = $client->sendRequest( $request);

        $response_data = json_decode( $response->getBody()->getContents(), true);

        $this->_logger->debug( 'Http response data ['.print_r( $response_data, true).']');

        return $response_data;
    }

    public function createImage( $data)
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->_apiKey,
        ];

        $this->_logger->debug( 'Performing request');

        $config = [];

        $client = $this->_httpFactory->getHttpClient( $config);
        $request = $this->_httpFactory->buildRequest( IHttpFactory::METHOD_POST, 'https://api.openai.com/v1/images/generations', $headers, $data);

        $response = $client->sendRequest( $request);

        $response_data = json_decode( $response->getBody()->getContents(), true);

        $this->_logger->debug( 'Http response ['.$response->getStatusCode().']');

        return $response_data;
    }

    // UTIL
    public function __toString()
    {
        return get_class( $this).'[]';
    }


}
