<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Disconnect;

/**
 * Interface DisconnectTaskEnqueuerInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Disconnect
 * @internal
 */
interface DisconnectTaskEnqueuerInterface
{
    /**
     * @param string $mode
     *
     * @return void
     */
    public function enqueueDisconnectTask(string $mode) : void;
}
