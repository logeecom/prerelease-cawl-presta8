<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
/**
 * Interface ConnectionProxy
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies
 * @internal
 */
interface ConnectionProxyInterface
{
    /**
     * Tests if connection is valid.
     *
     * @param ConnectionDetails $connectionDetails
     *
     * @return bool
     */
    public function isConnectionValid(ConnectionDetails $connectionDetails) : bool;
}
