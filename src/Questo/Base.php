<?php

namespace Questo;

use Questo\Service\MigrationService;
use Questo\Service\StatusService;
use Questo\Service\SubscriptionUserService;
use Questo\Service\AdminService;
use Questo\Service\CacheService;
use Questo\Service\ContentService;
use Questo\Service\LoggerService;
use Questo\Service\OAuthService;
use Questo\Service\ConfigService;
use Questo\Service\WordpressExtraStorage;
use Adquesto\SDK\PositioningSettings;
use Adquesto\SDK\Content;


class Base
{
    /**
     * @var OAuthService
     */
    public $oAuthService;

    /**
     * @var CacheService
     */
    public $cacheService;

    /**
     * @var ContentService
     */
    public $contentService;

    /**
     * @var AdminService
     */
    public $adminService;

    /**
     * @var ConfigService
     */
    public $configService;

    /**
     * @var SubscriptionUserService
     */
    public $subscriptionUserService;

    /**
     * @var MigrationService
     */
    public $migrationService;

    /**
     * @var StatusService
     */
    public $statusService;

    /**
     * @var Base
     */
    private static $instance;

    /**
     * Init wordpress functions
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        session_set_cookie_params(3600);  // 1 hour
        session_start();

        $this->configService = $configService;
        global $wpdb;
        $this->subscriptionUserService = new SubscriptionUserService($wpdb);

        $this->oAuthService = new OAuthService();
        $this->oAuthService
            ->setSubscriptionUserService($this->subscriptionUserService);

        $this->cacheService = new CacheService();
        $this->contentService = new ContentService();
        $this->statusService = new StatusService();

        function lazy_option($optionName)
        {
            return function () use ($optionName) {
                return get_option($optionName);
            };
        }

        $positioning = get_option(ConfigService::OPTION_POSITIONING);
        if (empty($positioning)) {
            update_option(ConfigService::OPTION_POSITIONING, PositioningSettings::STRATEGY_UPPER);
        }

        $content = new Content(
            ConfigService::getBaseApiUrlWithSuffix(),
            lazy_option(ConfigService::OPTION_TOKEN),
            new WordpressExtraStorage(),
            new WordpressApiHttpClient(),
            PositioningSettings::factory(get_option(ConfigService::OPTION_POSITIONING))
        );

        $this->contentService
            ->setContent($content)
            ->setCacheService($this->cacheService)
            ->setOAuthService($this->oAuthService)
            ->setSubscriptionUserService($this->subscriptionUserService)
            ->setStatusService($this->statusService);

        if (is_admin()) {
            $this->migrationService = new MigrationService($wpdb);
            $this->adminService = new AdminService();
            $this->adminService
                ->setContentService($this->contentService)
                ->setConfigService($this->configService);

            add_filter(
                'plugin_action_links_' . $this->configService->pluginBasename,
                array($this->adminService, 'addSettingsLink')
            );

            $pathToFile = $this->configService->getFilePathWithPluginDir();

            //hooks for activation and deactivation the plugin
            register_activation_hook($pathToFile, [$this, 'activation']);
            register_deactivation_hook($pathToFile, [$this, 'deactivation']);
            add_action('upgrader_process_complete', [$this, 'upgrade'], 10, 2);
        }

        //show message after activate
        add_action('admin_notices', array($this, 'showActivationMessage'));
        add_filter('plugin_locale', array($this, 'forceDefaultLanguage'), 10000, 2);

        add_action('update_option_' . ConfigService::OPTION_DISPLAY_SETTINGS,
            array($this, 'displaySettingsSave'), 10, 2);

        //load languages
        load_plugin_textdomain('questo', false, $this->configService->dirName . DIRECTORY_SEPARATOR . 'languages');

        self::$instance = $this;
    }

    /**
     * @return Base
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @param string $locale
     * @param string $domain
     * @return string
     */
    public function forceDefaultLanguage($locale, $domain)
    {
        if ($domain === 'questo') {
            if (!in_array($locale, array('pl_PL', 'en_US'))) {
                return 'en_US';
            }
        }

        return $locale;
    }

    public function sendSettingUpdate($values)
    {
        $client = new WordpressApiHttpClient();
        $url = sprintf(ConfigService::getApiSettingsUpdate(), get_option(ConfigService::OPTION_TOKEN));
        $client->post($url, $values);
    }

    public function collectSettings($option)
    {
        return [
            'posts' => $option['postTypes'],
            'categories' => array_map(function($categoryId) {
                return get_the_category_by_ID($categoryId);
            }, $option['categories']),
        ];
    }

    public function displaySettingsSave($old, $new)
    {
        $settings = json_decode($new, true);
        $this->sendSettingUpdate($this->collectSettings($settings));
    }

    public function sendAllSettings()
    {
        $settings = json_decode(get_option(ConfigService::OPTION_DISPLAY_SETTINGS), true);
        $this->sendSettingUpdate($this->collectSettings($settings));
    }

    /**
     * Activation plugin hook
     */
    public function activation()
    {
        try {
            if (ConfigService::getDisabledRequirements()) {
                include($this->configService->getTemplatePath('activationMessageIframe.php'));
                exit();
            }

            $token = $this->getCheckedOption(ConfigService::API_TOKEN, '__QUESTO_TOKEN_FOR_WORDPRESS__');
            $oAuthClientId = $this->getCheckedOption(ConfigService::OAUTH_CLIENT_ID, '__QUEST_OAUTH_CLIENT_ID__');
            $oAuthSecret = $this->getCheckedOption(ConfigService::OAUTH_SECRET, '__QUESTO_OAUTH_SECRET__');

            $subscriptionAvailable = $this->getCheckedOption(
                ConfigService::SUBSCRIPTION_AVAILABLE,
                '__QUESTO_SUBSCRIPTION_AVAILABLE__'
            );

            $this->createIfOptionNotExist(array(
                ConfigService::OPTION_TOKEN                  => $token,
                ConfigService::OPTION_OAUTH_CLIENT_ID        => $oAuthClientId,
                ConfigService::OPTION_OAUTH_SECRET           => $oAuthSecret,
                ConfigService::OPTION_SUBSCRIPTION_AVAILABLE => $subscriptionAvailable,
            ));

            $this->statusService->updateServiceStatusOption();

            set_transient('show_activation_message', true, 5);

            LoggerService::activation(ConfigService::getWordpressConfiguration());
            $this->cacheService->createCacheDir();
            $this->contentService->updateJavascript();
            $this->migrationService->createTable();
            $this->migrationService->applyAll();

            $displaySettings = json_decode(get_option(ConfigService::OPTION_DISPLAY_SETTINGS), true);

            if (!$displaySettings) {
                $this->adminService->forceUpdateDisplaySettings();
            }
            if (!get_role(ConfigService::ROLE_ADQUESTO_SUBSCRIBER)) {
                add_role(
                    ConfigService::ROLE_ADQUESTO_SUBSCRIBER,
                    ConfigService::ROLE_ADQUESTO_SUBSCRIBER,
                    array('read' => true)
                );
            }
            $this->sendAllSettings();
        } catch (\Exception $e) {
            LoggerService::error($e);
            throw $e;
        }
    }

    /**
     * @param string $value
     * @param string $valueToCheck
     * @return string
     */
    public function getCheckedOption($value, $valueToCheck)
    {
        if ($value == $valueToCheck) {
            return '';
        }

        return $value;
    }

    /**
     * @param $options
     */
    public function createIfOptionNotExist($options)
    {
        foreach ($options as $name => $value) {
            if (!get_option($name)) {
                update_option($name, $value);
            }
        }
    }

    /**
     * Deactivation plugin hook
     */
    public function deactivation()
    {
        LoggerService::deactivation();
        try {
            delete_option(ConfigService::OPTION_JAVASCRIPT);
            delete_option(ConfigService::OPTION_JAVASCRIPT_LAST_UPDATE_TIME);

            $this->migrationService->removeAll();
            $this->migrationService->dropTable();

            $this->cacheService->deleteCacheDir();
        } catch (\Exception $e) {
            LoggerService::error($e);
            throw $e;
        }
    }

    public function showActivationMessage()
    {
        if (get_transient('show_activation_message')) {
            include($this->configService->getTemplatePath('activationMessage.php'));
        }
    }

    public function upgrade($upgrader_object, $options)
    {
        $pathToFile = $this->configService->getFilePathWithPluginDir();

        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            foreach ($options['plugins'] as $each_plugin) {
                if ($each_plugin == $pathToFile) {
                    $this->migrationService->createTable();
                    $this->migrationService->applyAll();
                }
            }
        }
    }
}