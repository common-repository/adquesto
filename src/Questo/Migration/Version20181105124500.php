<?php

namespace Questo\Migration;


use Questo\Service\SubscriptionUserService;

class Version20181105124500 extends BaseMigration
{
    public function apply()
    {
        $subscriptionService = new SubscriptionUserService($this->wpdb);
        $subscriptionService->createTable();
    }

    public function remove()
    {
        $subscriptionService = new SubscriptionUserService($this->wpdb);
        $subscriptionService->dropTable();
    }
}
