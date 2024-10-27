<?php

namespace Questo\Service;


use Questo\WordpressApiHttpClient;

class LoggerService
{
    const ERROR = 1;
    const ACTIVATION = 2;
    const DEACTIVATION = 3;

    protected static $types = array(
        self::ACTIVATION    => 'activation',
        self::ERROR         => 'error',
        self::DEACTIVATION  => 'deactivation',
    );

    /**
     * @param array|string $value
     */
    public static function activation($value)
    {
        self::log(self::ACTIVATION, $value);
    }

    public static function deactivation()
    {
        self::log(self::DEACTIVATION);
    }

    /**
     * @param \Exception $e
     */
    public static function error(\Exception $e)
    {
        $value = array(
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTraceAsString(),
        );
        self::log(self::ERROR, $value);
    }

    /**
     * @param int $type
     * @param array $value
     */
    public static function log($type, $value = array())
    {
        if (isset(self::$types)) {
            $client = new WordpressApiHttpClient();
            $url = sprintf(ConfigService::getLogUrl(), get_option(ConfigService::OPTION_TOKEN));
            $client->post($url, [
                'type'  => self::$types[$type],
                'value' => $value,
                'url'   => get_site_url(),
            ]);
        }
    }
}