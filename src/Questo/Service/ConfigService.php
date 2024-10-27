<?php

namespace Questo\Service;

class ConfigService
{
    /**
     * Version
     */
    const VERSION = '1.1.50';

    /**
     * Path to public files
     */
    const PUBLIC_PATH = '/public/';

    /**
     * Token
     */
    const API_TOKEN = '__QUESTO_TOKEN_FOR_WORDPRESS__';

    /**
     * Default subscription available
     */
    const SUBSCRIPTION_AVAILABLE = '__QUESTO_SUBSCRIPTION_AVAILABLE__';

    /**
     * Oauth credentials
     */
    const OAUTH_CLIENT_ID = '__QUEST_OAUTH_CLIENT_ID__';
    const OAUTH_SECRET = '__QUESTO_OAUTH_SECRET__';

    /**
     * URL to update javascript
     */
    const BASE_API = 'https://api.adquesto.com';

    /**
     * Suffix to standard endpoints
     */
    const BASE_API_SUFFIX = '/v1/services/';

    /**
     * Names of options in wordpress
     */
    const OPTION_TOKEN = 'questo_token';
    const OPTION_JAVASCRIPT = 'questo_javascript';
    const OPTION_JAVASCRIPT_LAST_UPDATE_TIME = 'questo_javascript_last_update_time';
    const OPTION_OAUTH_CLIENT_ID = 'questo_oauth_client_id';
    const OPTION_OAUTH_SECRET = 'questo_oauth_secret';
    const OPTION_SUBSCRIPTION_AVAILABLE = 'questo_subscription_available';
    const OPTION_SERVICE_STATUS = 'questo_service_status';
    const OPTION_DISPLAY_SETTINGS = 'questo_display_settings';
    const OPTION_NEWLY_ADDED_CATEGORIES = 'questo_newly_added_categories';
    const OPTION_MASTER_SWITCH = 'questo_master_switch';
    const OPTION_HAS_ACTIVE_CAMPAIGNS = 'questo_has_active_campaigns';
    const OPTION_POSITIONING = 'questo_positioning';
    const OPTION_HIDE_FOR_USERS = 'questo_display_for_users';

    /**
     * Content settings
     */
    const CONTENT_NUMBER_OF_SENTENCES = 10;
    const CONTENT_AD_CONTAINER = '<div id="%s"></div>';
    const CONTENT_MAIN_QUEST_ID = '__MAIN_QUEST_ID__';
    const CONTENT_REMINDER_QUEST_ID = '__REMINDER_QUEST_ID__';

    /**
     * OAuth settings
     */
    const OAUTH_SCOPE = 'read_profile';

    const OAUTH_PROVIDER_URL = 'https://system.adquesto.com';
    const OAUTH_RESOURCE_ENDPOINT = '/oauth2/me';
    const OAUTH_AUTHORIZATION_ENDPOINT = '/subscriber';
    const OAUTH_TOKEN_ENDPOINT = '/oauth2/token';
    const OAUTH_CALLBACK_ACTION = 'questo_oauth_callback';
    const OAUTH_COOKIE_TEMPORARY_USER_ID = 'questo_oauth_temporary_user_id';
    const OAUTH_UPDATE_USER_ACTION = 'questo_oauth_update_user';
    const OAUTH_SUBSCRIPTION_REDIRECT = 'questo_oauth_subscription_redirect';

    const PHP_REQUIRED_VERSION = 50400;
    const ROLE_ADQUESTO_SUBSCRIBER = 'adquesto_subscriber';

    /**
     * @var string
     */
    public $filePath;

    /**
     * @var string
     */
    public $dirPath;

    /**
     * @var string
     */
    public $dirName;

    /**
     * @var string
     */
    public $pluginBasename;

    /**
     * @param string $pluginFilePath
     */
    public function __construct($pluginFilePath)
    {
        $this->filePath = $pluginFilePath;
        $this->pluginBasename = plugin_basename($pluginFilePath);
        $this->dirPath = dirname($this->filePath);
        $this->dirName = basename(dirname($pluginFilePath));
    }

    /**
     * @return string
     */
    public static function getBaseOAuthProviderUrl()
    {
        return self::OAUTH_PROVIDER_URL;
    }

    /**
     * @return string
     */
    public static function getBaseApiUrl()
    {
        return self::BASE_API;
    }

    /**
     * @return string
     */
    public static function getBaseApiUrlWithSuffix()
    {
        return self::BASE_API . self::BASE_API_SUFFIX;
    }

    /**
     * @return string
     */
    public static function getApiJSUrl()
    {
        return self::getBaseApiUrlWithSuffix() . '%s/javascript';
    }

    /**
     * @return string
     */
    public static function getLogUrl()
    {
        return self::getBaseApiUrlWithSuffix() . '%s/log';
    }

    /**
     * @return string
     */
    public static function getApiIsSubscriptionAvailableUrl()
    {
        return self::getBaseApiUrlWithSuffix() . '%s/has-subscriptions';
    }

    /**
     * @return string
     */
    public static function getApiStatusUrl()
    {
        return self::getBaseApiUrlWithSuffix() . '%s/status';
    }

    /**
     * @return string
     */
    public static function getApiSettingsUpdate()
    {
        return self::getBaseApiUrlWithSuffix() . '%s/wp-settings';
    }

    /**
     * @param string $templateName
     * @return string
     */
    public function getTemplatePath($templateName)
    {
        return $this->dirPath . '/template/' . $templateName;
    }

    /**
     * @param string $file
     * @return string
     */
    public function getPublicUrl($file)
    {
        return plugins_url(self::PUBLIC_PATH . $file, $this->getFilePathWithPluginDir());
    }

    /**
     * @return string
     */
    public function getFilePathWithPluginDir()
    {
        return basename(dirname($this->filePath)) . DIRECTORY_SEPARATOR . basename($this->filePath);
    }

    /**
     * @param string $file
     * @return string
     */
    public function getCssUrl($file)
    {
        return $this->getPublicUrl('css/' . $file);
    }

    /**
     * @param string $file
     * @return string
     */
    public function getJavascriptUrl($file)
    {
        return $this->getPublicUrl('js/' . $file);
    }

    /**
     * @param string $file
     * @return string
     */
    public function getImageUrl($file)
    {
        return $this->getPublicUrl('img/' . $file);
    }

    /**
     * @return int
     */
    public static function getPHPVersionId()
    {
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
            return $version[0] * 10000 + $version[1] * 100 + $version[2];
        }

        return PHP_VERSION_ID;
    }

    /**
     * @return bool
     */
    public static function isCorrectPHPVersion()
    {
        return self::getPHPVersionId() >= ConfigService::PHP_REQUIRED_VERSION;
    }

    /**
     * @return array
     */
    public static function getRequirements()
    {
        $requirements = array(
            'php-5.4.0' => self::isCorrectPHPVersion(),
            'wp-3.4.0'  => get_bloginfo('version') >= '3.4',
        );

        $extensions = self::getRequiredExtensions();

        foreach ($extensions as $extension) {
            $requirements['ext-' . $extension] = extension_loaded($extension);
        }

        $functions = self::getRequiredFunctions();

        foreach ($functions as $function) {
            $requirements[$function] = function_exists($function);
        }

        return $requirements;
    }

    /**
     * @return array
     */
    public static function getDisabledRequirements()
    {
        $requirements = array();

        foreach (self::getRequirements() as $extension => $enabled) {
            if (!$enabled) {
                $requirements[$extension] = !$enabled;
            }
        }

        return $requirements;
    }

    /**
     * @return array
     */
    private static function getRequiredExtensions()
    {
        return array('curl', 'date', 'hash', 'json', 'pcre', 'pdo', 'session', 'spl', 'mbstring');
    }

    /**
     * @return array
     */
    private static function getRequiredFunctions()
    {
        return array(
            'hash',
            'hash_equals',
            'http_build_query',
            'http_response_code',
            'file_get_contents',
            'json_encode',
            'mb_strlen',
            'strlen'
        );
    }

    /**
     * @return array
     */
    public static function getWordpressConfiguration()
    {
        $allPlugins = get_plugins();
        $activePlugins = get_option('active_plugins');
        $activePluginsNames = array();
        if (is_array($activePlugins)) {
            $activePluginsNames = array_map(function($plugin) use ($allPlugins) {
                return !empty($allPlugins[$plugin]['Name']) ? $allPlugins[$plugin]['Name'] : '';
            }, $activePlugins);
        }

        $config = array(
            'plugin-version'    => ConfigService::VERSION,
            'wp-version'        => get_bloginfo('version'),
            'php-version'       => self::getPHPVersionId(),
            'active-plugins'    => $activePluginsNames,
            'current-theme'     => wp_get_theme()->name,
        );

        $extensions = self::getRequiredExtensions();

        foreach ($extensions as $extension) {
            $config['ext-' . $extension] = extension_loaded($extension);
        }

        return $config;
    }
}
