<?php

namespace {
    use CAWL\OnlinePayments\Classes\Bootstrap;
    require_once __DIR__ . '/vendor/autoload.php';
    if (!\class_exists('CAWL\\OnlinePayments\\Classes\\Bootstrap')) {
        // During the upgrade it is possible that old vendor is required already, so we need failback autoloader
        require_once __DIR__ . '/upgrade/Autoloader.php';
        \Autoloader::setCoreLibSourcePath('worldline/integration-core/src/');
        \spl_autoload_register('\\Autoloader::loader');
    }
    try {
        $config = \json_decode(\file_get_contents(__DIR__ . '/config.json'), \true);
        Bootstrap::boot($config['MODULE_NAME'], $config['BRAND']);
    } catch (\Throwable $e) {
        throw new \Exception('Bootstrap module error: ' . $e->getMessage());
    }
}
