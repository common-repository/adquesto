<?php

namespace Questo\Service;

use Adquesto\SDK\NetworkErrorException;
use Adquesto\SDK\SubscriptionsContextProvider;
use Adquesto\SDK\ElementsContextProvider;
use Adquesto\SDK\Content;

class ContentService extends BaseService
{
    /**
     * @var Content
     * */
    private $content;

    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * @var OAuthService
     */
    private $oAuthService;

    /**
     * @var SubscriptionUserService
     */
    private $subscriptionUserService;

    /**
     * @var StatusService
     */
    private $statusService;

    /**
     * Init wordpress functions
     */
    public function __construct()
    {
        //endpoint to update
        add_action('wp_ajax_nopriv_questo_force_update_javascript', array($this, 'ajaxForceUpdateJavascript'));
        add_action('wp_ajax_questo_force_update_javascript', array($this, 'ajaxForceUpdateJavascript'));
        add_action('wp_ajax_nopriv_questo_get_options', array($this, 'ajaxGetQuestoOptions'));
        add_action('wp_ajax_questo_get_options', array($this, 'ajaxGetQuestoOptions'));

        //filer for add an ad to content
        add_filter('the_content', array($this, 'overwriteTheContent'), PHP_INT_MAX);
    }

    /**
     * @param $fullContent
     *
     * @return string
     * @throws NetworkErrorException
     */
    public function overwriteTheContent($fullContent)
    {
        //if is a list
        if (!is_singular() || ConfigService::getDisabledRequirements()) {
            return $this->removeQuestoHereFromText($fullContent);
        }

        global $post;
        $isDisabled = get_post_meta($post->ID, 'adquesto_disabled_in_the_post', true);
        if ($isDisabled) {
            return $this->removeQuestoHereFromText($fullContent);
        }

        return $this->prepareContent($post, $fullContent);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function removeQuestoHereFromText($text)
    {
        //fix for wordpress 3.4
        $text = str_replace('<div class="' . Content::MANUAL_QUEST_CLASS . '"></div>', '', $text);

        return str_replace('<div class="' . Content::MANUAL_QUEST_CLASS . '" contenteditable="false"></div>', '', $text);
    }

    protected function getCurrentUrl()
    {
        $refUrl = remove_query_arg('oauth-error', get_permalink());
        if (strpos($refUrl, ConfigService::OAUTH_SUBSCRIPTION_REDIRECT) !== false) {
            $refUrl = get_site_url();
        }
        return $refUrl;
    }

    protected function getRefUrlQuery()
    {
        return http_build_query(array('ref' => $this->getCurrentUrl()));
    }

    public static function isMasterSwitchEnabled()
    {
        $value = get_option(ConfigService::OPTION_MASTER_SWITCH, "");
        if ($value === "") {
            return (bool) get_option(ConfigService::OPTION_TOKEN);
        }

        return (bool) $value;
    }

    public static function hasActiveCampaigns()
    {
        return (bool) get_option(ConfigService::OPTION_HAS_ACTIVE_CAMPAIGNS);
    }

    public static function isUserEligibleToHideQuest()
    {
        static $roles = array('editor', 'administrator', 'author');
        $user = wp_get_current_user();
        if ($user) {
            return count(array_intersect($roles, $user->roles)) > 0;
        }

        return false;
    }

    /**
     * @param WP_Post $post
     * @param string $content
     *
     * @return string
     * @throws NetworkErrorException
     */
    private function prepareContent($post, $content)
    {
        $isDraft = !(get_post_status() == 'publish' && is_preview() == false);
        if (!$isDraft && (
            !$this->statusService->isEnabled() ||
            !self::isMasterSwitchEnabled() ||
            (get_option(ConfigService::OPTION_HIDE_FOR_USERS) && self::isUserEligibleToHideQuest()))) {

            return $content;
        }

        $mainQuestId = md5(uniqid(uniqid()));
        $reminderQuestId = md5(uniqid(uniqid()));

        $user = wp_get_current_user();
        $userExist = $user->exists();
        $subscriptionUser = $this->subscriptionUserService->findByUserId($user->ID);
        $hasAddFreeUser = $userExist && $subscriptionUser;

        $isSubscriptionActive = $this->subscriptionUserService->isSubscriptionActive($subscriptionUser);
        $subscriptionDaysLeft = $this->subscriptionUserService->getSubscriptionDaysLeft($subscriptionUser);
        $isSubscriptionRecurring = ($subscriptionUser ? $subscriptionUser->recurring_payments : 0);

        $isSubscriptionAvailable = get_option(ConfigService::OPTION_SUBSCRIPTION_AVAILABLE);
        $hasActiveCampaigns = self::hasActiveCampaigns();

        $authorizationError = (isset($_GET['oauth-error']) ? $_GET['oauth-error'] : '');
        $authorizationUri = admin_url(
            'admin-ajax.php?action=' . ConfigService::OAUTH_SUBSCRIPTION_REDIRECT . '&' . $this->getRefUrlQuery()
        );
        $logoutUri = wp_logout_url($this->getCurrentUrl());

        $userLogin = ($subscriptionUser ? $subscriptionUser->user_login : '');

        $containerMainQuest = sprintf(ConfigService::CONTENT_AD_CONTAINER, $mainQuestId);
        $containerReminderQuest = sprintf(ConfigService::CONTENT_AD_CONTAINER, $reminderQuestId);

        $javascript = $this->content->javascript(array(
            new ElementsContextProvider(
                $mainQuestId,
                $reminderQuestId,
                $isDraft,
                $hasActiveCampaigns
            ),
            new SubscriptionsContextProvider(array(
                SubscriptionsContextProvider::IS_SUBSCRIPTION_ACTIVE    => (int)$isSubscriptionActive,
                SubscriptionsContextProvider::IS_SUBSCRIPTION_RECURRING => (int)$isSubscriptionRecurring,
                SubscriptionsContextProvider::IS_SUBSCRIPTION_DAYS_LEFT => $subscriptionDaysLeft,
                SubscriptionsContextProvider::IS_SUBSCRIPTION_AVAILABLE => (int)$isSubscriptionAvailable,
                SubscriptionsContextProvider::AUTHORIZATION_ERROR       => (string)$authorizationError,
                SubscriptionsContextProvider::IS_LOGGED_IN              => (int)$hasAddFreeUser,
                SubscriptionsContextProvider::AUTHORIZATION_URI         => $authorizationUri,
                SubscriptionsContextProvider::LOGOUT_URI                => $logoutUri,
                SubscriptionsContextProvider::USER_LOGIN                => $userLogin,
            )),
        ), $isDraft);

        if ($this->content->hasQuestoClassInHTML($content)) {
            $preparedContent = $this->content->manualPrepare(
                $content,
                $containerMainQuest,
                $containerReminderQuest
            );
            $preparedContent->setJavaScript($javascript);
            return $preparedContent;
        }

        if ($this->canShowOnThePost($post)) {
            $preparedContent = $this->content->autoPrepare(
                $content,
                $containerMainQuest,
                $containerReminderQuest
            );
            $preparedContent->setJavaScript($javascript);
            return $preparedContent;
        }

        return $content;
    }

    /**
     * @return bool
     */
    public function updateJavascript()
    {
        $javascript = null;
        try {
            $javascript = $this->content->requestJavascript();
        } catch (NetworkErrorException $e) {
            if ($e->getCode() == 404) {
                $this->content->getStorage()->set(null);
            }
        }

        if ($javascript) {
            $this->content->getStorage()->set($javascript);
        }

        return (bool)$javascript;
    }

    /**
     * Force update javascript
     */
    public function ajaxForceUpdateJavascript()
    {
        $javascriptUpdated = $this->updateJavascript();

        $this->getSubscriptionUserService()->updateSubscriptionAvailableOption();

        if ($javascriptUpdated) {
            $this->cacheService->clearCachePlugins();
        }

        //we need to use this because wp_send_json is from 3.5.0
        $this->sendJson(array('status' => $javascriptUpdated));
    }

    /**
     * Get quest options
     */
    public function ajaxGetQuestoOptions()
    {
        $secret = isset($_POST['secret']) ? $_POST['secret'] : false;

        if (!$secret || $secret !== get_option(ConfigService::OPTION_OAUTH_SECRET)) {
            $this->sendJson(array('status' => false, 'message' => 'Invalid secret', 'options' => null));
        }

        $all_options = wp_load_alloptions();
        $quest_keys = preg_grep('/^questo_/', array_keys($all_options));
        $options = array_intersect_key($all_options, array_flip($quest_keys));
        $this->sendJson(array('status' => true, 'options' => $options));
    }

    /**
     * @param object $post
     * @return bool
     */
    public function canShowOnThePost($post)
    {
        $displaySettings = json_decode(get_option(ConfigService::OPTION_DISPLAY_SETTINGS), true);

        if ($displaySettings) {
            $availablePostTypes = isset($displaySettings['postTypes']) ? $displaySettings['postTypes'] : array();
            $availableCategories = isset($displaySettings['categories']) ? $displaySettings['categories'] : array();
            $isMatchedPostType = in_array($post->post_type, $availablePostTypes);
            $isMatchedCategory = false;
            $categories = get_the_category($post->ID);


            if ($availableCategories && $categories) {
                foreach ($categories as $category) {
                    $isMatchedCategory = in_array($category->term_id, $availableCategories);
                    if ($isMatchedCategory) {
                        break;
                    }
                }
            }

            if (!$categories) {
                $isMatchedCategory = true;
            }

            if (!$isMatchedPostType || !$isMatchedCategory) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return CacheService
     */
    public function getCacheService()
    {
        return $this->cacheService;
    }

    /**
     * @param CacheService $cacheService
     * @return ContentService
     */
    public function setCacheService(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;

        return $this;
    }

    /**
     * @return OAuthService
     */
    public function getOAuthService()
    {
        return $this->oAuthService;
    }

    /**
     * @param OAuthService $oAuthService
     * @return ContentService
     */
    public function setOAuthService($oAuthService)
    {
        $this->oAuthService = $oAuthService;

        return $this;
    }

    /**
     * @return SubscriptionUserService
     */
    public function getSubscriptionUserService()
    {
        return $this->subscriptionUserService;
    }

    /**
     * @param SubscriptionUserService $subscriptionUserService
     * @return ContentService
     */
    public function setSubscriptionUserService($subscriptionUserService)
    {
        $this->subscriptionUserService = $subscriptionUserService;

        return $this;
    }

    public function setContent(Content $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param StatusService $statusService
     * @return ContentService
     */
    public function setStatusService($statusService)
    {
        $this->statusService = $statusService;

        return $this;
    }
}
