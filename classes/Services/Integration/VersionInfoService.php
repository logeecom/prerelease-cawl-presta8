<?php

namespace OnlinePayments\Classes\Services\Integration;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use OnlinePayments\Core\BusinessLogic\Domain\Version\VersionInfo;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

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
    public function getVersionInfo(): VersionInfo
    {
        /** @var \Module $module */
        $module = ServiceRegister::getService(\Module::class);

        return new VersionInfo($module->version, $module->version);
    }
}
