<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection\Mocks;

use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies\ConnectionProxyInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;

/**
 * Class MockConnectionProxy
 *
 * @package AdminAPI\Mocks
 */
class MockConnectionProxyInterface implements ConnectionProxyInterface
{
    public bool $success = false;

    public function isConnectionValid(ConnectionDetails $connectionDetails): bool
    {
        return $this->success;
    }
}
