<?php

namespace AdminAPI\StoreAPI\Mocks;

use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Credentials;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;

class MockConnectionConfigRepository implements ConnectionConfigRepositoryInterface
{
    private ConnectionDetails $connectionDetails;

    public function __construct()
    {
        $this->connectionDetails = new ConnectionDetails(
            ConnectionMode::test(),
            null,
            new Credentials('test', 'test', 'test', 'test', 'test')
        );
    }

    public function saveConnection(ConnectionDetails $connectionDetails): void
    {
        $this->connectionDetails = $connectionDetails;
    }

    public function getConnection(): ?ConnectionDetails
    {
        return $this->connectionDetails;
    }

    public function getOldestConnection(): ?ConnectionDetails
    {
        return $this->connectionDetails;
    }

    public function getOldestConnectedStore(): string
    {
        return '1';
    }

    public function disconnect(): void
    {
    }
}