<?php
/**
 * Plugin Name: Adquesto
 * Author: Adquesto
 * Version: 1.1.50
 * Plugin URI: https://adquesto.com
 * Description: The new revolution in online advertising
 */
require_once('vendor/autoload.php');

try {
    $questoBase = new \Questo\Base(new \Questo\Service\ConfigService(__FILE__));
} catch (\Exception $e) {
    \Questo\Service\LoggerService::error($e);
    throw $e;
}
