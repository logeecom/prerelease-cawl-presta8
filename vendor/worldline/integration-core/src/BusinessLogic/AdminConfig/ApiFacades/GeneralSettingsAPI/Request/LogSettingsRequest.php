<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Request\Request;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidLogRecordsLifetimeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogRecordsLifetime;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogSettings;
/**
 * Class LogSettingsRequest
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request
 * @internal
 */
class LogSettingsRequest extends Request
{
    protected bool $debugMode;
    protected int $days;
    /**
     * @param bool $debugMode
     * @param int $days
     */
    public function __construct(bool $debugMode, int $days)
    {
        $this->debugMode = $debugMode;
        $this->days = $days;
    }
    /**
     * @inheritDoc
     *
     * @throws InvalidLogRecordsLifetimeException
     */
    public function transformToDomainModel() : object
    {
        return new LogSettings($this->debugMode, LogRecordsLifetime::create($this->days));
    }
}
