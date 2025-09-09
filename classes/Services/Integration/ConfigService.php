<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Classes\Utility\Url;
use CAWL\OnlinePayments\Core\Bootstrap\Configuration\Configuration;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class ConfigService
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class ConfigService extends Configuration
{
    private const INTEGRATION_NAME = 'PrestaShop';
    public function getAsyncProcessUrl(string $guid) : string
    {
        $params = ['guid' => $guid];
        if ($this->isAutoTestMode()) {
            $params['auto-test'] = 1;
        }
        return Url::getFrontUrl('asyncprocess', $params);
    }
    public function getIntegrationVersion() : string
    {
        return \_PS_VERSION_;
    }
    public function getIntegrationName() : string
    {
        return self::INTEGRATION_NAME;
    }
    public function getPluginName() : string
    {
        $module = ServiceRegister::getService(\Module::class);
        return $module->displayName . ' ' . $this->getIntegrationName();
    }
    public function getPluginVersion() : string
    {
        $module = ServiceRegister::getService(\Module::class);
        return $module->version;
    }
}
