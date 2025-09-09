<?php

namespace CAWL\OnlinePayments\Core\Infrastructure\Logger\Interfaces;

use CAWL\OnlinePayments\Core\Infrastructure\Logger\LogData;
/**
 * Interface LoggerAdapter.
 *
 * @package OnlinePayments\Core\Infrastructure\Logger\Interfaces
 * @internal
 */
interface LoggerAdapter
{
    /**
     * Log message in system
     *
     * @param LogData $data
     *
     * @return void
     */
    public function logMessage(LogData $data) : void;
}
