<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Controller;

use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Request\ConnectionRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Response\ConnectionConfigResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Response\ConnectionResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\ConnectionService;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionDetailsException;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionModeException;

/**
 * Class ConnectionController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Controller
 */
class ConnectionController
{
    protected ConnectionService $connectionService;

    public function __construct(ConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * @param ConnectionRequest $connectionRequest
     *
     * @return ConnectionResponse
     *
     * @throws InvalidConnectionDetailsException
     * @throws InvalidConnectionModeException
     */
    public function connect(ConnectionRequest $connectionRequest): ConnectionResponse
    {
        $this->connectionService->connect($connectionRequest->transformToDomainModel());

        return new ConnectionResponse();
    }

    /**
     * @return ConnectionConfigResponse
     */
    public function getConnectionConfig(): ConnectionConfigResponse
    {
        return new ConnectionConfigResponse($this->connectionService->getConnectionConfig());
    }
}
