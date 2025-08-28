<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogSettings;

/**
 * Interface LogSettingsRepositoryInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Repositories
 */
interface LogSettingsRepositoryInterface
{
    /**
     * @return LogSettings|null
     */
    public function getLogSettings(): ?LogSettings;

    /**
     * @param LogSettings $logSettings
     *
     * @return void
     */
    public function saveLogSettings(LogSettings $logSettings): void;

    /**
     * @param string $mode
     *
     * @return void
     */
    public function deleteByMode(string $mode): void;
}
