<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\LogCleanup;

/**
 * Interface LogCleanupTaskServiceInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\LogCleanup
 * @internal
 */
interface LogCleanupTaskServiceInterface
{
    /**
     * @return int
     */
    public function findLatestExecutionTimestamp() : int;
    /**
     * @return void
     */
    public function enqueueLogCleanupTask() : void;
}
