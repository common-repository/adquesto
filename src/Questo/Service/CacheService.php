<?php

namespace Questo\Service;

class CacheService
{
    /**
     * Clear existing plugins
     */
    public function clearCachePlugins()
    {
        // Check if get_plugins() function exists. This is required on the front end of the
        // site, since it is in a file that is normally only loaded in the admin.
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $pluginsToClear = array(
            array(
                'name'   => 'W3 Total Cache',
                'method' => 'w3tc_flush_all',
            ),
            array(
                'name'   => 'WP Super Cache',
                'method' => 'wp_cache_clean_cache',
            ),
            array(
                'name'   => 'WP Fastest Cache',
                'class'  => 'wpFastestCache',
                'method' => 'deleteCache',
            ),
            array(
                'name'   => 'Comet Cache',
                'class'  => 'WebSharks\CometCache\Classes\ApiBase',
                'method' => 'clear',
            ),
        );

        $installedPlugins = get_plugins();
        $pluginNames = array();

        foreach ($installedPlugins as $installedPlugin) {
            $pluginNames[$installedPlugin['Name']] = $installedPlugin;
        }
        foreach ($pluginsToClear as $plugin) {
            $currentPlugin = (isset($pluginNames[$plugin['name']]) ? $pluginNames[$plugin['name']] : false);
            if ($currentPlugin) {
                switch ($plugin['name']) {
                    case 'W3 Total Cache':
                        $method = $plugin['method'];

                        if (function_exists($method)) {
                            $method();
                        }

                        break;
                    case 'WP Super Cache':
                        global $file_prefix;
                        $method = $plugin['method'];

                        if (function_exists($method)) {
                            $method($file_prefix);
                        }

                        break;
                    case 'WP Fastest Cache':
                    case 'Comet Cache':
                        $object = $this->createExternalPluginCacheObject($plugin['class']);
                        if ($object) {
                            $this->clearExternalPluginCache($object, $plugin['method']);
                        }
                        break;
                }
            }
        }
    }

    /**
     * @param string $class
     * @return object|bool
     */
    public function createExternalPluginCacheObject($class)
    {
        return (class_exists($class) ? new $class() : false);
    }

    /**
     * @param object $object
     * @param string $method
     */
    public function clearExternalPluginCache($object, $method)
    {
        if (method_exists($object, $method)) {
            $object->$method();
        }
    }

    /**
     * @return string
     */
    public function getWpContentDir()
    {
        return dirname(WP_PLUGIN_DIR);
    }

    /**
     * Adquesto wp plugin cache dir path
     * @return string
     */
    public function getPluginCacheDir()
    {
        return $this->getWpContentDir() . '/adquesto-plugin';
    }

    /**
     * @return bool
     */
    public function checkPluginCacheDir()
    {
        return is_dir($this->getPluginCacheDir());
    }

    /**
     * @return bool
     */
    public function createCacheDir()
    {
        if (!$this->checkPluginCacheDir()) {
            return @mkdir($this->getPluginCacheDir(), 0755, true);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function deleteCacheDir()
    {
        if ($this->checkPluginCacheDir()) {
            foreach (new \DirectoryIterator($this->getPluginCacheDir()) as $fileInfo) {
                if(!$fileInfo->isDot()) {
                    @unlink($fileInfo->getPathname());
                }
            }
            return @rmdir($this->getPluginCacheDir());
        }
        return false;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getCachePath($key)
    {
        return $this->getPluginCacheDir() . '/' . $key;
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setValue($key, $value)
    {
        if (is_writable($this->getPluginCacheDir())) {
            return @file_put_contents($this->getCachePath($key), $value);
        }
        return false;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getValue($key)
    {
        return @file_get_contents($this->getCachePath($key));
    }
}