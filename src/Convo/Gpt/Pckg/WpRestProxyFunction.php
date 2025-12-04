<?php

namespace Convo\Gpt\Pckg;

use Convo\Core\Params\SimpleParams;
use Convo\Core\Workflow\AbstractWorkflowComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Gpt\IChatFunction;

class WpRestProxyFunction extends AbstractWorkflowComponent implements IChatFunction, IConversationElement
{
    private $_name;
    private $_description;
    private $_parameters;
    private $_defaults;
    private $_required;
    private $_method;
    private $_endpoint;
    private $_pagination;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_name        =   $properties['name'];
        $this->_description =   $properties['description'];
        $this->_parameters  =   $properties['parameters'];
        $this->_defaults  =   $properties['defaults'];
        $this->_required  =   $properties['required'];
        $this->_method  =   $properties['method'];
        $this->_endpoint  =   $properties['endpoint'];
        $this->_pagination  =   $properties['pagination'];
    }


    // element
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        /** @var \Convo\Gpt\IChatFunctionContainer $container */
        $container = $this->findAncestor('\Convo\Gpt\IChatFunctionContainer');
        $container->registerFunction($this);
    }

    public function execute(IConvoRequest $request, IConvoResponse $response, array $data)
    {
        $this->_logger->debug('Got data decoded [' . $this->getName() . '] - [' . print_r($data, true) . ']');
        $data = array_merge($this->_getDefaults(), $data);
        $this->_logger->info('Got data with defaults [' . $this->getName() . '] - [' . print_r($data, true) . ']');

        $method   = strtoupper($this->evaluateString($this->_method));
        $endpoint = ltrim($this->evaluateString($this->_endpoint), '/');
        $pagination = $this->evaluateString($this->_pagination);

        // Handle pagination via cursor
        if ($pagination && isset($data['cursor'])) {
            $cursorData = json_decode(base64_decode($data['cursor']), true);
            if (is_array($cursorData)) {
                unset($data['cursor']);
                $data = array_merge($data, $cursorData);
            }
        }

        $route = "/wp/v2/{$endpoint}";
        $this->_logger->debug('Executing [' . $method . '] - [' . $route . ']');
        $restRequest = new \WP_REST_Request($method, $route);

        if ($method === 'GET') {
            foreach ($data as $key => $val) {
                $restRequest->set_param($key, $val);
            }
        } else {
            $restRequest->set_body_params($data);
        }

        /** @var \WP_REST_Response $restResponse */
        $restResponse = rest_do_request($restRequest);
        $responseData = $restResponse->get_data();

        // Handle pagination response wrapping
        if ($pagination && is_array($responseData)) {
            $headers = $restResponse->get_headers();
            $page    = isset($data['page']) ? intval($data['page']) : 1;
            $perPage = isset($data['per_page']) ? intval($data['per_page']) : 10;
            $totalPages = isset($headers['X-WP-TotalPages']) ? intval($headers['X-WP-TotalPages']) : null;

            if ($totalPages && $page < $totalPages) {
                $nextCursor = base64_encode(json_encode([
                    'page' => $page + 1,
                    'per_page' => $perPage
                ]));

                $responseData = [
                    'results' => $responseData,
                    'nextCursor' => $nextCursor
                ];
            }
        }

        if (is_string($responseData)) {
            return $responseData;
        }

        return json_encode($responseData);
    }


    private function _getDefaults()
    {
        $defaults = $this->evaluateString($this->_defaults);
        if (is_array($defaults)) {
            return $defaults;
        }
        return [];
    }

    public function accepts($functionName)
    {
        return $this->getName() === $functionName;
    }

    public function getName()
    {
        return $this->evaluateString($this->_name);
    }

    public function getDefinition()
    {
        $parameters = $this->getService()->evaluateArgs($this->_parameters, $this);
        if (empty($parameters)) {
            $parameters = new \stdClass();
        }
        return [
            'name' => $this->getName(),
            'description' => $this->evaluateString($this->_description),
            'parameters' => [
                'type' => 'object',
                'properties' => $parameters,
                'required' => $this->evaluateString($this->_required),
            ],
        ];
    }


    // TODO: refactor separate chat from scoped function
    public function initParams() {}

    public function restoreParams($executionId) {}

    public function getFunctionParams()
    {
        return new SimpleParams();
    }


    // UTIL
    public function __toString()
    {
        return parent::__toString() . '[' . $this->_name . ']';
    }
}
