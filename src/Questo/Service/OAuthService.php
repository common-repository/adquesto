<?php

namespace Questo\Service;

use fkooman\OAuth\Client\AccessToken;
use fkooman\OAuth\Client\Http\CurlHttpClient;
use fkooman\OAuth\Client\OAuthClient;
use fkooman\OAuth\Client\Provider;
use fkooman\OAuth\Client\SessionTokenStorage;

class OAuthService extends BaseService
{
    /**
     * @var string
     */
    private $temporaryUserId;

    /**
     * @var SubscriptionUserService
     */
    private $subscriptionUserService;

    /**
     * @var SessionTokenStorage
     */
    private $tokenStorage;

    /**
     * @var OAuthClient
     */
    private $client;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * Init functions for oAuth2
     */
    public function __construct()
    {
        //endpoint to authorize the user
        add_action('wp_ajax_nopriv_' . ConfigService::OAUTH_CALLBACK_ACTION, array($this, 'callbackEndpoint'));
        add_action('wp_ajax_' . ConfigService::OAUTH_CALLBACK_ACTION, array($this, 'callbackEndpoint'));

        //endpoint to update information about the user
        add_action('wp_ajax_nopriv_' . ConfigService::OAUTH_UPDATE_USER_ACTION, array($this, 'updateUserEndpoint'));
        add_action('wp_ajax_' . ConfigService::OAUTH_UPDATE_USER_ACTION, array($this, 'updateUserEndpoint'));

        //endpoint to redirect admin oauth
        add_action('wp_ajax_nopriv_' . ConfigService::OAUTH_SUBSCRIPTION_REDIRECT, array($this, 'subscriptionRedirect'));
        add_action('wp_ajax_' . ConfigService::OAUTH_SUBSCRIPTION_REDIRECT, array($this, 'subscriptionRedirect'));

        //generate the userId to bind the access token to
        if (isset($_COOKIE[ConfigService::OAUTH_COOKIE_TEMPORARY_USER_ID])) {
            $this->temporaryUserId = $_COOKIE[ConfigService::OAUTH_COOKIE_TEMPORARY_USER_ID];
        }

        if (!$this->temporaryUserId) {
            $this->temporaryUserId = md5(uniqid());
            setcookie(ConfigService::OAUTH_COOKIE_TEMPORARY_USER_ID, $this->temporaryUserId, time() + 60 * 60 * 6, COOKIEPATH, COOKIE_DOMAIN);
        }

        $this->tokenStorage = new SessionTokenStorage();
    }

    /**
     * redirect for admin to check oauth
     */
    public function subscriptionRedirect()
    {
        wp_redirect($this->getAuthorizeUri(!empty($_GET['ref']) ? $_GET['ref'] : ''));
        exit;
    }

    /**
     * setup access token
     */
    public function updateUserEndpoint()
    {
        $id = $_POST['id'];

        $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-[4][0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        if (!preg_match($pattern, $id)) {
            $this->sendJson(array('status' => false, 'message' => 'incorrect id'), 422);
        }
        $subscriptionUser = $this->subscriptionUserService->findByUid($id);
        if (!$subscriptionUser) {
            $this->sendJson(array('status' => false, 'message' => 'user with this id not exist'), 422);
        }

        $this->temporaryUserId = $id;

        //remove all old access tokens for this user
        $sessionKey = sprintf('_oauth2_token_%s', $this->temporaryUserId);
        if (isset($_SESSION[$sessionKey])) {
            unset($_SESSION[$sessionKey]);
        }

        $tokenData = array(
            'access_token'  => $subscriptionUser->access_token,
            'refresh_token' => $subscriptionUser->refresh_token,
            'issued_at'     => $subscriptionUser->access_token_issued_at,
            'provider_id'   => $this->getProvider()->getProviderId(),
            'token_type'    => 'Bearer',
            'scope'         => $this->getScope(),
        );

        $expiresIn = (int)$subscriptionUser->access_token_expires_in;

        if ($expiresIn > 0) {
            $tokenData['expires_in'] = $expiresIn;
        }

        try {
            $accessToken = new AccessToken($tokenData);
            $this->tokenStorage->storeAccessToken($this->temporaryUserId, $accessToken);
            $response = $this->getClient()->get(
                $this->getProvider(),
                $this->temporaryUserId,
                $this->getScope(),
                $this->getResourceUrl()
            );

            //if we don't have response try to refresh token
            if (!$response) {
                $this->tokenStorage->deleteAccessToken($this->temporaryUserId, $accessToken);

                //hard refresh token by oAuthClient
                $tokenData['expires_in'] = 1;
                $accessToken = new AccessToken($tokenData);
                $this->tokenStorage->storeAccessToken($this->temporaryUserId, $accessToken);
                $response = $this->getClient()->get(
                    $this->getProvider(),
                    $this->temporaryUserId,
                    $this->getScope(),
                    $this->getResourceUrl()
                );
            }

            //if we don't have response after when we refreshed token, we have to return an error
            if (!$response) {
                $this->sendJson(array('status' => false, 'message' => 'invalid tokens'), 422);
            }

            $user = json_decode($response->getBody());
            $subscriptionDate = $user->subscriptionDate;
            $recurringPayments = $user->recurringPayments;
            $userLogin = $user->email;

            if ($subscriptionDate) {
                $subscriptionDate = new \DateTime($subscriptionDate);
                $subscriptionDate = $subscriptionDate->format('Y-m-d H:i:s');
            }

            $accessToken = $this->getAccessToken($this->temporaryUserId);

            //if yes, update existing
            $this->subscriptionUserService->update(
                $subscriptionUser->uid,
                $subscriptionUser->user_id,
                $userLogin,
                $recurringPayments,
                $subscriptionDate,
                $accessToken->getToken(),
                $accessToken->getRefreshToken(),
                $accessToken->getIssuedAt()->format('Y-m-d H:i:s'),
                $accessToken->getExpiresIn()
            );
            $this->sendJson(array('status' => true));
        } catch (\Exception $exception) {
            LoggerService::error($exception);
            throw $exception;
        }
        die();
    }

    /**
     * Find an AccessToken in the list that matches this scope, bound to
     * providerId and userId.
     *
     * @param int $userId
     * @return false|AccessToken
     */
    public function getAccessToken($userId)
    {
        $accessTokenList = $this->tokenStorage->getAccessTokenList($userId);
        /** @var AccessToken $accessToken */
        foreach ($accessTokenList as $accessToken) {
            if ($this->provider->getProviderId() !== $accessToken->getProviderId()) {
                continue;
            }
            if ($this->getScope() !== $accessToken->getScope()) {
                continue;
            }

            return $accessToken;
        }

        return false;
    }

    /**
     * setup access token, create wordpress user
     */
    public function callbackEndpoint()
    {
        try {
            //remove all old access tokens for this user
            $sessionKey = sprintf('_oauth2_token_%s', $this->temporaryUserId);
            if (isset($_SESSION[$sessionKey])) {
                unset($_SESSION[$sessionKey]);
            }

            //handle the callback from the OAuth server
            $this->getClient()->handleCallback(
                $this->getProvider(),
                $this->temporaryUserId,
                $_GET['code'], // the authorization_code
                $_GET['state'] // the state
            );

            //send a request and get an user
            $response = $this->getClient()->get(
                $this->getProvider(),
                $this->temporaryUserId,
                $this->getScope(),
                $this->getResourceUrl()
            );

            if ($response !== false) {
                $this->insertOrUpdateSubscriptionUserFromResponse($response);
            }
        } catch (\Exception $exception) {
            LoggerService::error($exception);
            wp_redirect(add_query_arg(array('oauth-error' => $exception->getMessage()), $_GET['ref']));
            exit;
        }

        // remove last error from ref and redirect to the previous page
        $ref = remove_query_arg('oauth-error', $_GET['ref']);
        // add query param to prevent cache response by browser
        $ref = add_query_arg(array('t' => time()), $ref);

        wp_redirect($ref);
        exit;
    }

    /**
     * @param $response
     */
    public function insertOrUpdateSubscriptionUserFromResponse($response)
    {
        $user = json_decode($response->getBody());

        //try to find in database by an email
        $wpUser = get_user_by('email', $user->email);

        if (!$wpUser) {
            //if we didn't find the user create a new
            wp_create_user($user->email, md5(uniqid()), $user->email);
            $wpUser = get_user_by('email', $user->email);
            update_user_meta($wpUser->ID, 'show_admin_bar_front', 'false');
            $wpUser->set_role(ConfigService::ROLE_ADQUESTO_SUBSCRIBER);
        }

        $subscriptionDate = $user->subscriptionDate;
        $recurringPayments = $user->recurringPayments;

        if ($subscriptionDate) {
            $subscriptionDate = new \DateTime($subscriptionDate);
            $subscriptionDate = $subscriptionDate->format('Y-m-d H:i:s');
        }

        //check whether the ad user is exist
        $subscriptionUser = $this->subscriptionUserService->findByUserId($wpUser->ID);

        //if not, create one
        $method = ($subscriptionUser ? 'update' : 'insert');

        /** @var AccessToken $accessToken */
        $accessToken = current($this->tokenStorage->getAccessTokenList($this->temporaryUserId));

        //if yes, update existing
        $this->subscriptionUserService->$method(
            $user->uid,
            $wpUser->ID,
            $user->email,
            $recurringPayments,
            $subscriptionDate,
            $accessToken->getToken(),
            $accessToken->getRefreshToken(),
            $accessToken->getIssuedAt()->format('Y-m-d H:i:s'),
            $accessToken->getExpiresIn()
        );

        //after try to login the user
        wp_set_current_user($wpUser->ID, $wpUser->user_login);
        wp_set_auth_cookie($wpUser->ID);
        do_action('wp_login', $wpUser->user_login);

        //remove access token, we don't need this
        $this->tokenStorage->deleteAccessToken($this->temporaryUserId, $accessToken);
    }

    /**
     * @return string
     */
    public function getResourceUrl()
    {
        return ConfigService::getBaseApiUrl() . ConfigService::OAUTH_RESOURCE_ENDPOINT;
    }

    /**
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return ConfigService::getBaseOAuthProviderUrl() . ConfigService::OAUTH_AUTHORIZATION_ENDPOINT;
    }

    /**
     * @param $ref string refUrl query param
     * @return string
     */
    public function getAuthorizeUri($ref = null)
    {
        return $this->getClient()->getAuthorizeUri(
            $this->getProvider(),
            $this->temporaryUserId,
            $this->getScope(),
            $this->getCallbackUrl()
        ) . '&ref=' . $ref;
    }

    /**
     * @return string
     */
    public function getTokenUrl()
    {
        return ConfigService::getBaseApiUrl() . ConfigService::OAUTH_TOKEN_ENDPOINT;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return admin_url('admin-ajax.php?action=' . ConfigService::OAUTH_CALLBACK_ACTION);
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return get_option(ConfigService::OPTION_OAUTH_CLIENT_ID);
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return get_option(ConfigService::OPTION_OAUTH_SECRET);
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return ConfigService::OAUTH_SCOPE;
    }

    /**
     * @return OAuthClient
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new OAuthClient(
                $this->tokenStorage,
                new CurlHttpClient()
            );
        }

        return $this->client;
    }

    /**
     * @return Provider
     */
    public function getProvider()
    {
        if (!$this->provider) {
            $this->provider = new Provider(
                $this->getClientId(),
                $this->getSecret(),
                $this->getAuthorizationUrl(),
                $this->getTokenUrl()
            );
        }
        return $this->provider;
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
     * @return OAuthService
     */
    public function setSubscriptionUserService($subscriptionUserService)
    {
        $this->subscriptionUserService = $subscriptionUserService;

        return $this;
    }
}
