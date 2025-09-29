<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\IntegrationAPI\Controller;

use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\IntegrationAPI\Response\StateResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\ConnectionService;
/**
 * Class IntegrationController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\IntegrationAPI\Controller
 */
class IntegrationController
{
    private ConnectionService $connectionService;
    /**
     * @param ConnectionService $connectionService
     */
    public function __construct(ConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }
    /**
     * @return StateResponse
     */
    public function getState() : StateResponse
    {
        return $this->connectionService->isLoggedIn() ? StateResponse::payments() : StateResponse::connection();
    }
}
