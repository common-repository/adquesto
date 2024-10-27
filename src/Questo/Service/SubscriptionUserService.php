<?php

namespace Questo\Service;

class SubscriptionUserService extends BaseDatabaseService
{
    //TODO add index to searchable columns
    public function __construct($wpdb)
    {
        //endpoint to update
        add_action('wp_ajax_nopriv_questo_update_subscription_option', [$this, 'ajaxUpdateSubscriptionOption']);
        add_action('wp_ajax_questo_update_subscription_option', [$this, 'ajaxUpdateSubscriptionOption']);
        add_filter('gettext_with_context', [$this, 'translateRole'], 10, 1000);

        $this->wpdb = $wpdb;
        $this->tableName = $this->wpdb->prefix . 'adquesto_subscription_user';
        $this->columns = [
            'uid varchar(50) NOT NULL',
            'user_id bigint(20) NOT NULL',
            'user_login varchar(255)',
            'recurring_payments int(1)',
            'subscription_date datetime',
            'access_token varchar(255)',
            'refresh_token varchar(255)',
            'access_token_issued_at datetime',
            'access_token_expires_in int(5)',
        ];
    }

    /**
     * @param string $translation
     * @param string $text
     * @param string $context
     * @param string $domain
     * @return string
     */
    public function translateRole($translation, $text, $context, $domain)
    {
        $shouldBeTranslated = $domain != 'questo' &&
            $context === 'User role' &&
            $translation == ConfigService::ROLE_ADQUESTO_SUBSCRIBER;

        if ($shouldBeTranslated) {
            return __($translation, 'questo');
        }

        return $translation;
    }

    /**
     * Update subscription available option
     */
    public function ajaxUpdateSubscriptionOption()
    {
        $status = $this->updateSubscriptionAvailableOption();
        //we need to use this because wp_send_json is from 3.5.0
        $this->sendJson(['status' => $status]);
    }

    /**
     * Get subscription available option and update value in database
     *
     * @return bool
     */
    public function updateSubscriptionAvailableOption()
    {
        $subscriptionAvailable = 0;
        $json = @file_get_contents(
            sprintf(ConfigService::getApiIsSubscriptionAvailableUrl(), get_option(ConfigService::OPTION_TOKEN))
        );

        if ($json) {
            $json = json_decode($json, true);
            $subscriptionAvailable = (int)$json['subscription'];
        }
        update_option(ConfigService::OPTION_SUBSCRIPTION_AVAILABLE, $subscriptionAvailable);

        return (bool)$subscriptionAvailable;
    }

    /**
     * @param int $userId
     * @return object
     */
    public function findByUserId($userId)
    {
        return $this->wpdb->get_row(sprintf('SELECT * FROM %s WHERE user_id = %s', $this->tableName, $userId));
    }

    /**
     * @param int $uid
     * @return object
     */
    public function findByUid($uid)
    {
        return $this->wpdb->get_row(sprintf('SELECT * FROM %s WHERE uid = \'%s\'', $this->tableName, $uid));
    }

    /**
     * @param string $uid
     * @param int    $userId
     * @param string $userLogin
     * @param string $recurringPayments
     * @param string $subscriptionDate
     * @param string $accessToken
     * @param string $refreshToken
     * @param int    $accessTokenIssuedAt
     * @param int    $accessTokenExpiresIn
     * @return object
     * @internal param string $expiredAt
     */
    public function insert(
        $uid,
        $userId,
        $userLogin,
        $recurringPayments,
        $subscriptionDate,
        $accessToken,
        $refreshToken,
        $accessTokenIssuedAt,
        $accessTokenExpiresIn
    ) {
        $this->wpdb->insert(
            $this->tableName,
            [
                'uid'                     => $uid,
                'user_id'                 => $userId,
                'user_login'              => $userLogin,
                'recurring_payments'      => $recurringPayments,
                'subscription_date'       => $subscriptionDate,
                'access_token'            => $accessToken,
                'refresh_token'           => $refreshToken,
                'access_token_issued_at'  => $accessTokenIssuedAt,
                'access_token_expires_in' => $accessTokenExpiresIn,
            ]
        );

        return $this->findByUserId($userId);
    }

    /**
     * @param string $uid
     * @param int    $userId
     * @param string $userLogin
     * @param string $recurringPayments
     * @param string $subscriptionDate
     * @param string $accessToken
     * @param string $refreshToken
     * @param int    $accessTokenIssuedAt
     * @param int    $accessTokenExpiresIn
     * @return object
     * @internal param string $expiredAt
     */
    public function update(
        $uid,
        $userId,
        $userLogin,
        $recurringPayments,
        $subscriptionDate,
        $accessToken,
        $refreshToken,
        $accessTokenIssuedAt,
        $accessTokenExpiresIn
    ) {
        $this->wpdb->update(
            $this->tableName,
            [
                'uid'                     => $uid,
                'user_id'                 => $userId,
                'user_login'              => $userLogin,
                'recurring_payments'      => $recurringPayments,
                'subscription_date'       => $subscriptionDate,
                'access_token'            => $accessToken,
                'refresh_token'           => $refreshToken,
                'access_token_issued_at'  => $accessTokenIssuedAt,
                'access_token_expires_in' => $accessTokenExpiresIn,
            ],
            ['user_id' => $userId]
        );

        return $this->findByUserId($userId);
    }

    /**
     * @param object $subscriptionUser
     * @return bool
     */
    public function isSubscriptionActive($subscriptionUser)
    {
        try {
            if ($subscriptionUser) {
                $now = new \DateTime();
                if ($now < new \DateTime($subscriptionUser->subscription_date)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            LoggerService::error($e);
            return false;
        }

        return false;
    }

    /**
     * @param object $subscriptionUser
     * @return bool
     */
    public function getSubscriptionDaysLeft($subscriptionUser)
    {
        try {
            if ($subscriptionUser) {
                $subscriptionDate = new \DateTime($subscriptionUser->subscription_date);
                $now = new \DateTime();
                return $now->diff($subscriptionDate)->days + 1;
            }
        } catch (\Exception $e) {
            LoggerService::error($e);
            return 0;
        }

        return 0;
    }
}