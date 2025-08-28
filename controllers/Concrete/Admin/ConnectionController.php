<?php

namespace OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Classes\Utility\Request;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Request\ConnectionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionDetailsException;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionModeException;
use Tools;

/**
 * Class ConnectionController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 */
class ConnectionController extends ModuleAdminController
{
    /**
     * @return void
     *
     * @throws InvalidConnectionDetailsException
     * @throws InvalidConnectionModeException
     */
    public function displayAjaxConnect()
    {
        $requestData = Request::getPostData();
        $storeId = Tools::getValue('storeId');

        $connectionRequest = new ConnectionRequest(
            $requestData['mode'] ?? '',
            $requestData['testData']['pspid'] ?? '',
            $requestData['testData']['apiKey'] ?? '',
            $requestData['testData']['apiSecret'] ?? '',
            $requestData['testData']['webhooksKey'] ?? '',
            $requestData['testData']['webhooksSecret'] ?? '',
            $requestData['liveData']['pspid'] ?? '',
            $requestData['liveData']['apiKey'] ?? '',
            $requestData['liveData']['apiSecret'] ?? '',
            $requestData['liveData']['webhooksKey'] ?? '',
            $requestData['liveData']['webhooksSecret'] ?? '',
        );

        $result = AdminAPI::get()->connection($storeId)->connect($connectionRequest);

        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }

    /**
     * @return void
     */
    public function displayAjaxGetConnectionSettings()
    {
        $storeId = Tools::getValue('storeId');

        $result = AdminAPI::get()->connection($storeId)->getConnectionConfig();

        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
