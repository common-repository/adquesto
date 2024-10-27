<?php

namespace Questo\Service;

class StatusService extends BaseService
{
    public function __construct()
    {
        //endpoint to update
        add_action('wp_ajax_nopriv_questo_update_service_status_option', array($this, 'ajaxUpdateServiceStatusOption'));
        add_action('wp_ajax_questo_update_service_status_option', array($this, 'ajaxUpdateServiceStatusOption'));
    }

    /**
     * Update service status option
     */
    public function ajaxUpdateServiceStatusOption()
    {
        $updatedOptions = $this->updateServiceStatusOption();
        //we need to use this because wp_send_json is from 3.5.0
        $this->sendJson(array('status' => 'OK', 'updated_options' => $updatedOptions));
    }

    /**
     * Get service status option and update value in database
     *
     * @return array
     */
    public function updateServiceStatusOption()
    {
        $serviceStatus = 0;
        $subscriptionAvailable = 0;
        $hasActiveCampaigns = 0;

        $result = @file_get_contents(
            sprintf(ConfigService::getApiStatusUrl(), get_option(ConfigService::OPTION_TOKEN))
        );

        if ($result) {
            $result = json_decode($result, true);
            $serviceStatus = (int)$result['status'];
            $subscriptionAvailable = (int)$result['subscription'];
            $hasActiveCampaigns = (int)$result['hasActiveCampaigns'];
        }

        update_option(ConfigService::OPTION_SERVICE_STATUS, $serviceStatus);
        update_option(ConfigService::OPTION_SUBSCRIPTION_AVAILABLE, $subscriptionAvailable);
        update_option(ConfigService::OPTION_HAS_ACTIVE_CAMPAIGNS, $hasActiveCampaigns);

        return [
            ConfigService::OPTION_SERVICE_STATUS => $serviceStatus,
            ConfigService::OPTION_SUBSCRIPTION_AVAILABLE => $subscriptionAvailable,
            ConfigService::OPTION_HAS_ACTIVE_CAMPAIGNS => $hasActiveCampaigns,
        ];
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)get_option(ConfigService::OPTION_SERVICE_STATUS);
    }
}