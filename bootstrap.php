<?php

use OnlinePayments\Classes\Bootstrap;

require_once __DIR__ . '/vendor/autoload.php';

if (!class_exists('OnlinePayments\Classes\Bootstrap')) {
    // During the upgrade it is possible that old vendor is required already, so we need failback autoloader
    require_once __DIR__ . '/upgrade/Autoloader.php';

    Autoloader::setCoreLibSourcePath('worldline/integration-core/src/');

    spl_autoload_register('Autoloader::loader');
}

try {
    Bootstrap::boot('worldlineop', 'WOP');
} catch (\Throwable $e) {
    throw new \Exception('Bootstrap module error: ' . $e->getMessage());
}