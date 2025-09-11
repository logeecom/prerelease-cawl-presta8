<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Classes\Services\PaymentLink\OrderProviderService;
use CAWL\OnlinePayments\Classes\Services\PrestaShop\CancelService;
use CAWL\OnlinePayments\Classes\Services\PrestaShop\CaptureService;
use CAWL\OnlinePayments\Classes\Services\PrestaShop\RefundService;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
class TransactionController extends ModuleAdminController
{
    /**
     * @var OnlinePaymentsModule
     */
    public $module;
    /**
     * @throws \Exception
     */
    public function displayAjaxCapture()
    {
        $transaction = \Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign(['onlinePaymentsAjaxTransactionError' => $this->module->l('You do not have permission to capture funds.', 'TransactionController')]);
            //@formatter:on
            die(\json_encode(['result_html' => $this->module->hookAdminOrderCommon((int) $transaction['idOrder'])]));
        }
        $captureService = new CaptureService($this->module, $this->context->shop->id);
        $error = $captureService->handle($transaction);
        if (!empty($error)) {
            $this->context->smarty->assign('onlinePaymentsAjaxTransactionError', $error);
        } else {
            $this->context->smarty->assign('captureConfirmation', \true);
        }
        $html = $this->module->hookAdminOrderCommon((int) $transaction['idOrder']);
        die(\json_encode(['result_html' => $html, 'success' => \true]));
    }
    /**
     * @throws \Exception
     */
    public function displayAjaxRefund()
    {
        $transaction = \Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign(['onlinePaymentsAjaxTransactionError' => $this->module->l('You do not have permission to refund funds.', 'TransactionController')]);
            //@formatter:on
            die(\json_encode(['result_html' => $this->module->hookAdminOrderCommon((int) $transaction['idOrder'])]));
        }
        $refundService = new RefundService($this->module, $this->context->shop->id);
        $error = $refundService->handleFromExtension($transaction);
        if (!empty($error)) {
            $this->context->smarty->assign('onlinePaymentsAjaxTransactionError', $error);
        } else {
            $this->context->smarty->assign('refundConfirmation', \true);
        }
        $html = $this->module->hookAdminOrderCommon((int) $transaction['idOrder']);
        die(\json_encode(['result_html' => $html, 'success' => \true]));
    }
    /**
     * @throws \Exception
     */
    public function displayAjaxCancel()
    {
        $transaction = \Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign(['onlinePaymentsAjaxTransactionError' => $this->module->l('You do not have permission to cancel transactions.', 'TransactionController')]);
            //@formatter:on
            die(\json_encode(['result_html' => $this->module->hookAdminOrderCommon((int) $transaction['idOrder'])]));
        }
        $cancelService = new CancelService($this->module, $this->context->shop->id);
        $error = $cancelService->handleFromExtension($transaction);
        if (!empty($error)) {
            $this->context->smarty->assign('onlinePaymentsAjaxTransactionError', $error);
        } else {
            $this->context->smarty->assign('cancelConfirmation', \true);
        }
        $html = $this->module->hookAdminOrderCommon((int) $transaction['idOrder']);
        die(\json_encode(['result_html' => $html, 'success' => \true]));
    }
    /**
     * @throws \Exception
     */
    public function displayAjaxPaybylink()
    {
        $transaction = \Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign(['onlinePaymentsAjaxTransactionError' => $this->module->l('You do not have permission to cancel transactions.', 'TransactionController')]);
            //@formatter:on
            die(\json_encode(['result_html' => $this->module->hookAdminOrderCommon((int) $transaction['idOrder'])]));
        }
        $order = new \Order((int) $transaction['idOrder']);
        if (!\Validate::isLoadedObject($order)) {
            return $this->module->l('Unexpected error occurred during payment link creation.', 'TransactionController');
        }
        AdminAPI::get()->paymentLinks($this->context->shop->id)->create(new PaymentLinkRequest(new OrderProviderService($transaction['idOrder']), $this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectReturnPaymentLink', 'merchantReference' => (string) $order->id_cart])));
        $html = $this->module->hookAdminOrderCommon((int) $transaction['idOrder']);
        die(\json_encode(['result_html' => $html, 'success' => \true]));
    }
}
