<?php

namespace Questo\Migration;

use Questo\Service\BaseDatabaseService;

class BaseMigration extends BaseDatabaseService
{
    /**
     * Name of main file should be "Version+date+time" for example: Version20181105124500
     * @param $wpdb
     */
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * The function will be execute on activation hook
     */
    public function apply()
    {
    }

    /**
     * The function will be execute on deactivation hook
     */
    public function remove()
    {
    }
}