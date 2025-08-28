<?php

namespace OnlinePayments\Core\Infrastructure\Logger\Interfaces;

use OnlinePayments\Core\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter.
 *
 * @package OnlinePayments\Core\Infrastructure\Logger\Interfaces
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
    public function logMessage(LogData $data): void;
}
