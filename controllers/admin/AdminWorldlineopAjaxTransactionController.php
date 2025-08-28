<?php
/**
 * 2021 Worldline Online Payments
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 * @author    PrestaShop partner
 * @copyright 2021 Worldline Online Payments
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnlinePayments\Classes\Services\PaymentLink\OrderProviderService;
use OnlinePayments\Classes\Services\PrestaShop\CancelService;
use OnlinePayments\Classes\Services\PrestaShop\CaptureService;
use OnlinePayments\Classes\Services\PrestaShop\RefundService;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;

/**
 * Class AdminWorldlineopAjaxTransactionController
 */
class AdminWorldlineopAjaxTransactionController extends ModuleAdminController
{
    /** @var Worldlineop */
    public $module;

    /**
     * @throws Exception
     */
    public function displayAjaxCapture()
    {
        $transaction = Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign([
                'worldlineopAjaxTransactionError' => $this->module->l('You do not have permission to capture funds.', 'AdminWorldlineopAjaxTransactionController'),
            ]);
            //@formatter:on
            die(json_encode([
                'result_html' => $this->module->hookAdminOrderCommon((int) $transaction['idOrder']),
            ]));
        }

        $captureService = new CaptureService($this->module->name, $this->context->shop->id);

        $error = $captureService->handle($transaction);

        if (!empty($error)) {
            $this->context->smarty->assign('worldlineopAjaxTransactionError', $error);
        } else {
            $this->context->smarty->assign('captureConfirmation', true);
        }

        $html = $this->module->hookAdminOrderCommon((int) $transaction['idOrder']);

        die(json_encode(['result_html' => $html, 'success' => true]));
    }

    /**
     * @throws Exception
     */
    public function displayAjaxRefund()
    {
        $transaction = Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign([
                'worldlineopAjaxTransactionError' => $this->module->l('You do not have permission to refund funds.', 'AdminWorldlineopAjaxTransactionController'),
            ]);
            //@formatter:on
            die(json_encode([
                'result_html' => $this->module->hookAdminOrderCommon((int) $transaction['idOrder']),
            ]));
        }

        $refundService = new RefundService($this->module->name, $this->context->shop->id);

        $error = $refundService->handleFromExtension($transaction);

        if (!empty($error)) {
            $this->context->smarty->assign('worldlineopAjaxTransactionError', $error);
        } else {
            $this->context->smarty->assign('refundConfirmation', true);
        }

        $html = $this->module->hookAdminOrderCommon((int) $transaction['idOrder']);

        die(json_encode(['result_html' => $html, 'success' => true]));
    }

    /**
     * @throws Exception
     */
    public function displayAjaxCancel()
    {
        $transaction = Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign([
                'worldlineopAjaxTransactionError' => $this->module->l('You do not have permission to cancel transactions.', 'AdminWorldlineopAjaxTransactionController'),
            ]);
            //@formatter:on
            die(json_encode([
                'result_html' => $this->module->hookAdminOrderCommon((int) $transaction['idOrder']),
            ]));
        }

        $cancelService = new CancelService($this->module->name, $this->context->shop->id);

        $error = $cancelService->handleFromExtension($transaction);

        if (!empty($error)) {
            $this->context->smarty->assign('worldlineopAjaxTransactionError', $error);
        } else {
            $this->context->smarty->assign('cancelConfirmation', true);
        }

        $html = $this->module->hookAdminOrderCommon((int) $transaction['idOrder']);

        die(json_encode(['result_html' => $html, 'success' => true]));
    }

    /**
     * @throws Exception
     */
    public function displayAjaxPaybylink()
    {
        $transaction = Tools::getValue('transaction');
        if (!$this->access('edit')) {
            //@formatter:off
            $this->context->smarty->assign([
                'worldlineopAjaxTransactionError' => $this->module->l('You do not have permission to cancel transactions.', 'AdminWorldlineopAjaxTransactionController'),
            ]);
            //@formatter:on
            die(json_encode([
                'result_html' => $this->module->hookAdminOrderCommon((int)$transaction['idOrder']),
            ]));
        }

        AdminAPI::get()->paymentLinks($this->context->shop->id)->create(new PaymentLinkRequest(
            new OrderProviderService($transaction['idOrder']),
            $this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectReturnPaymentLink']),
        ));

        $html = $this->module->hookAdminOrderCommon((int)$transaction['idOrder']);

        die(json_encode(['result_html' => $html, 'success' => true]));
    }
}
