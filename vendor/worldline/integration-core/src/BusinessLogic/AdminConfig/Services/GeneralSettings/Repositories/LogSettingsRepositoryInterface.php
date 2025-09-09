<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogSettings;
/**
 * Interface LogSettingsRepositoryInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Repositories
 * @internal
 */
interface LogSettingsRepositoryInterface
{
    /**
     * @return LogSettings|null
     */
    public function getLogSettings() : ?LogSettings;
    /**
     * @param LogSettings $logSettings
     *
     * @return void
     */
    public function saveLogSettings(LogSettings $logSettings) : void;
    /**
     * @param string $mode
     *
     * @return void
     */
    public function deleteByMode(string $mode) : void;
}
