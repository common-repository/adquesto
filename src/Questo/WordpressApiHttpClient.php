<?php

namespace Questo;


use Adquesto\SDK\HttpClient;
use Adquesto\SDK\NetworkErrorException;

class WordpressApiHttpClient implements HttpClient
{
    public function get($url, array $headers = array(), $throwWhenNot200 = false)
    {
        $response = wp_remote_get($url, array('headers' => $headers));
        $this->error_handle($response, $throwWhenNot200);
        try {
            return $response['body'];
        } catch(\Exception $e) {
            return null;
        }
    }

    public function post($url, array $data = array(), array $headers = array(), $throwWhenNot200 = false, $isJSON = true)
    {
        if ($isJSON) {
            $data = json_encode($data);
            $headers['Content-type'] = 'application/json';
        }
        $response = wp_remote_post($url , array('body' => $data, 'headers' => $headers));
        $this->error_handle($response, $throwWhenNot200);
        try {
            return $response['body'];
        } catch(\Exception $e) {
            return null;
        }
    }

    private function error_handle($response, $throwWhenNot200)
    {
        if ($throwWhenNot200) {
            if (is_wp_error($response)) {
                throw new NetworkErrorException('WP_Error: ' . $response->get_error_message());
            }
            elseif ($response['response']['code'] != 200) {
                throw new NetworkErrorException('Response code is not 200', $response['response']['code']);
            }
        }
    }
}
