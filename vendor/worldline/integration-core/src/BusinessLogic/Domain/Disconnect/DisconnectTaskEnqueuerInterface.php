<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Disconnect;

/**
 * Interface DisconnectTaskEnqueuerInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Disconnect
 */
interface DisconnectTaskEnqueuerInterface
{
    /**
     * @return void
     */
    public function enqueueDisconnectTask(): void;
}
