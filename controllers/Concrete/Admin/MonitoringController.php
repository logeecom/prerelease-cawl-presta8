<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\Services\Domain\Repositories\MonitoringLogRepository;
use CAWL\OnlinePayments\Classes\Services\Domain\Repositories\WebhookLogRepository;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use Tools;
/**
 * Class MonitoringController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 */
class MonitoringController extends ModuleAdminController
{
    public function displayAjaxGetMonitoringLogs()
    {
        $storeId = Tools::getValue('storeId');
        $pageNumber = Tools::getValue('pageNumber');
        $pageSize = Tools::getValue('pageSize');
        $searchTerm = Tools::getValue('searchTerm');
        $result = AdminAPI::get()->monitoringLogs($storeId)->getMonitoringLogs($pageNumber, $pageSize, $searchTerm);
        if (!$result->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJson($result);
        }
        $arrayResult = $result->toArray();
        /** @var MonitoringLogRepository $repository */
        $repository = ServiceRegister::getService(MonitoringLogRepositoryInterface::class);
        foreach ($arrayResult['monitoringLogs'] as $key => $monitoringLog) {
            $arrayResult['monitoringLogs'][$key]['orderLink'] = $repository->getOrderUrlByCartId($monitoringLog['orderId']);
        }
        OnlinePaymentsPrestaShopUtility::dieJsonArray($arrayResult);
    }
    public function displayAjaxDownloadMonitoringLogs()
    {
        $storeId = Tools::getValue('storeId');
        $result = AdminAPI::get()->monitoringLogs($storeId)->downloadMonitoringLogs();
        $fileName = \tempnam(\sys_get_temp_dir(), 'onlinepayments_monitoring_logs');
        $out = \fopen($fileName, 'w');
        \fwrite($out, \json_encode($result->toArray()));
        \fclose($out);
        OnlinePaymentsPrestaShopUtility::dieFile($fileName, 'onlinepayments_monitoring_logs.json');
    }
    public function displayAjaxGetWebhookLogs()
    {
        $storeId = Tools::getValue('storeId');
        $pageNumber = Tools::getValue('pageNumber');
        $pageSize = Tools::getValue('pageSize');
        $searchTerm = Tools::getValue('searchTerm');
        $result = AdminAPI::get()->monitoringLogs($storeId)->getWebhookLogs($pageNumber, $pageSize, $searchTerm);
        if (!$result->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJson($result);
        }
        $arrayResult = $result->toArray();
        /** @var WebhookLogRepository $repository */
        $repository = ServiceRegister::getService(WebhookLogRepositoryInterface::class);
        foreach ($arrayResult['webhookLogs'] as $key => $webhookLog) {
            $arrayResult['webhookLogs'][$key]['orderLink'] = $repository->getOrderUrlByCartId($webhookLog['orderId']);
        }
        OnlinePaymentsPrestaShopUtility::dieJsonArray($arrayResult);
    }
    public function displayAjaxDownloadWebhookLogs()
    {
        $storeId = Tools::getValue('storeId');
        $result = AdminAPI::get()->monitoringLogs($storeId)->downloadWebhookLogs();
        $fileName = \tempnam(\sys_get_temp_dir(), 'onlinepayments_webhook_logs');
        $out = \fopen($fileName, 'w');
        \fwrite($out, \json_encode($result->toArray()));
        \fclose($out);
        OnlinePaymentsPrestaShopUtility::dieFile($fileName, 'onlinepayments_webhook_logs.json');
    }
}
