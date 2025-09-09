<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Version\VersionInfo;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class VersionInfoService
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class VersionInfoService implements VersionService
{
    /**
     * @inheritDoc
     */
    public function getVersionInfo() : VersionInfo
    {
        /** @var \Module $module */
        $module = ServiceRegister::getService(\Module::class);
        return new VersionInfo($module->version, $module->version);
    }
}
