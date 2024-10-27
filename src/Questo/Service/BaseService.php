<?php

namespace Questo\Service;

class BaseService
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @return ConfigService
     */
    public function getConfigService()
    {
        return $this->configService;
    }

    /**
     * @param ConfigService $configService
     * @return BaseService
     */
    public function setConfigService($configService)
    {
        $this->configService = $configService;
        return $this;
    }

    /**
     * we need to use this because wp_send_json is from 3.5.0
     * @param mixed $response
     * @param int   $responseCode
     */
    public function sendJson($response, $responseCode = 200)
    {
        http_response_code($responseCode);
        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo json_encode($response);
        die();
    }
}