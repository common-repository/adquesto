<?php

namespace Questo\Service;

use Adquesto\SDK\JavascriptStorage;
use Questo\Service\CacheService;


class WordpressExtraStorage implements JavascriptStorage
{
    const JAVASCRIPT_MINLEN = 1024; // We are sure that our javascript should be longer than this
    const JAVASCRIPT_CACHE_KEY = 'plugin.cache';
    const OPTION_JAVASCRIPT = 'questo_javascript';
    const OPTION_JAVASCRIPT_LAST_UPDATE_TIME = 'questo_javascript_last_update_time';

    public function __construct()
    {
        $this->cacheService = new CacheService();
    }

    public function get()
    {
        // Get from fill cache
        $value = $this->cacheService->getValue(static::JAVASCRIPT_CACHE_KEY);
        if (!$value || strlen($value) < static::JAVASCRIPT_MINLEN) {
            // Get from DB
            $value = get_option(static::OPTION_JAVASCRIPT);
            if (substr($value, 0, 4) !== '!fun') {
                $value = base64_decode($value);
            }
        }

        return $value;
    }

    public function set($contents)
    {
        update_option(static::OPTION_JAVASCRIPT_LAST_UPDATE_TIME, time());

        // Set file cache
        $this->cacheService->setValue(static::JAVASCRIPT_CACHE_KEY, $contents);
        // Set DB value
        update_option(static::OPTION_JAVASCRIPT, base64_encode($contents));
    }

    public function valid()
    {
        $content = $this->get();
        if (!$content || strlen($content) < static::JAVASCRIPT_MINLEN) {
            return false;
        }
        $lastUpdate = (int) get_option(static::OPTION_JAVASCRIPT_LAST_UPDATE_TIME);
        $previousDay = time() - (60 * 60 * 24);
        //update javascript every 24h
        if ($lastUpdate < $previousDay) {
            return false;
        }
        return (bool)$content;
    }
}