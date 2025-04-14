<?php

declare(strict_types=1);

namespace Convo\Gpt\Tools;

abstract class AbstractRestFunctions
{
    abstract public function register();

    protected function _makeRestFn($method, $endpoint)
    {
        return function ($data) use ($method, $endpoint) {
            $request = new \WP_REST_Request($method, "/wp/v2/{$endpoint}");
            if ($method === 'GET') {
                foreach ($data as $key => $val) {
                    $request->set_param($key, $val);
                }
            } else {
                $request->set_body_params($data);
            }
            $response = rest_do_request($request);
            return json_encode($response->get_data());
        };
    }


    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
