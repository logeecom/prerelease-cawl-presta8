<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\Proxies;

use CAWL\OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies\ConnectionProxyInterface as BaseConnectionProxy;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionDetailsException;
class ConnectionProxy implements BaseConnectionProxy
{
    private const CONNECTION_VALID = 'OK';
    private MerchantClientFactory $clientFactory;
    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
    /**
     * @inheritDoc
     *
     * @throws InvalidConnectionDetailsException
     */
    public function isConnectionValid(ConnectionDetails $connectionDetails) : bool
    {
        return $this->clientFactory->get($connectionDetails)->services()->testConnection()->getResult() === self::CONNECTION_VALID;
    }
}
