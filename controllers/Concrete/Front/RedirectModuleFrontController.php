<?php

namespace OnlinePayments\Controllers\Concrete\Front;

use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

/**
 * Class RedirectModuleFrontController.
 *
 * @package OnlinePayments\Controllers\Concrete\Front
 */
class RedirectModuleFrontController extends \ModuleFrontController
{
    public const ACTIONS =
        ['redirectReturnHosted', 'redirectReturnIframe', 'redirectReturnInternalIframe', 'redirectReturnPaymentLink'];

    /**
     * @return array
     */
    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['robots'] = 'noindex';

        return $page;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function display()
    {
        $this->setTemplate("module:{$this->module->name}/views/templates/front/redirect.tpl");

        $action = \Tools::getValue('action');
        $merchantReference = \Tools::getValue('merchantReference');

        if (!in_array($action, self::ACTIONS)) {
            \Tools::redirect($this->context->link->getPageLink('order', null, null, ['step' => 3]));
        }

        $this->context->smarty->assign([
            'module' => $this->module->name,
            'img_path' => sprintf(__PS_BASE_URI__ . 'modules/%s/views/img/', $this->module->name),
            'redirectController' => $this->context->link->getModuleLink(
                $this->module->name,
                'redirect',
                ['action' => $action, 'merchantReference' => $merchantReference]
            ),
            'returnMac' => \Tools::getValue('RETURNMAC'),
            'hostedCheckoutId' => \Tools::getValue('hostedCheckoutId'),
            'paymentId' => \Tools::getValue('paymentId'),
            'merchantReference' => \Tools::getValue('merchantReference'),
            'customerToken' => \Tools::getToken(),
        ]);

        return parent::display();
    }

    public function displayAjaxRedirectExternal()
    {
        $productId = \Tools::getValue('productId', null);
        $response = CheckoutAPI::get()
            ->hostedCheckout((string)$this->context->shop->id)
            ->createSession(new HostedCheckoutSessionRequest(
                ServiceRegister::getService(CartProvider::class),
                $this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectReturnHosted']),
                $productId ? PaymentProductId::parse($productId) : null,
                \Tools::getValue('tokenId', null)
            ));

        if (!$response->isSuccessful()) {
            \Tools::redirect($this->context->link->getPageLink(
                'order',
                null,
                null,
                ['step' => 3, $this->module->name . 'DisplayPaymentTopMessage' => 1]
            ));
        }

        \Tools::redirect($response->getRedirectUrl());
    }

    public function displayAjaxRedirectReturnHosted()
    {
        $paymentId = \Tools::getValue('paymentId', '');
        if (empty($paymentId)) {
            $paymentId = \Tools::getValue('hostedCheckoutId', '');
        }

        CheckoutAPI::get()
            ->payment((string)$this->context->shop->id)
            ->startWaitingForOutcomeInBackground(
                PaymentId::parse($paymentId),
                \Tools::getValue('RETURNMAC', null),
            );

        $this->displayAjaxRedirectReturnInternalIframe();
    }

    public function displayAjaxRedirectReturnPaymentLink()
    {
        $merchantReference = \Tools::getValue('merchantReference', '');

        CheckoutAPI::get()
            ->payment((string)$this->context->shop->id)
            ->startWaitingForOutcomeInBackground(null, null, $merchantReference);

        $this->displayAjaxRedirectReturnPaymentLinkInternalIframe();
    }

    /**
     * @param bool $displayErrorMessage
     */
    public function dieOrderStep3($displayErrorMessage = true)
    {
        $params = ['step' => 3];
        if (true === $displayErrorMessage) {
            $params[$this->module->name . 'DisplayPaymentTopMessage'] = 1;
        }
        die(json_encode([
            'redirectUrl' => $this->context->link->getPageLink('order', null, null, $params),
        ]));
    }

    /**
     * @throws \PrestaShopException
     */
    public function displayAjaxRedirectReturnIframe()
    {
        CheckoutAPI::get()
            ->payment((string)$this->context->shop->id)
            ->startWaitingForOutcomeInBackground(
                PaymentId::parse(\Tools::getValue('paymentId', '')),
                \Tools::getValue('RETURNMAC', null),
            );

        $this->displayAjaxRedirectReturnInternalIframe();
    }

    public function displayAjaxRedirectReturnInternalIframe()
    {
        $customer = new \Customer($this->context->customer->id);
        if (false === \Validate::isLoadedObject($customer)) {
            $this->dieOrderStep3();
        }

        $paymentId = \Tools::getValue('paymentId', '');
        if (empty($paymentId)) {
            $paymentId = \Tools::getValue('hostedCheckoutId', '');
        }

        $response = CheckoutAPI::get()
            ->payment((string)$this->context->shop->id)
            ->getPaymentOutcome(
                PaymentId::parse($paymentId),
                \Tools::getValue('RETURNMAC', null),
            );

        if (!$response->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => false,
                'message' => $this->module->l('An error occurred while fetching the payment status.', 'payment'),
            ]);
        }

        $cart = new \Cart((int)$response->getPaymentTransaction()->getMerchantReference());
        if (
            false === \Validate::isLoadedObject($cart) ||
            (int)$cart->id_customer !== (int)$customer->id
        ) {
            $this->dieOrderStep3();
        }

        if ($response->isWaiting()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => true,
                'waiting' => true,
            ]);
        }


        if (false !== \Order::getIdByCartId((int)$response->getPaymentTransaction()->getMerchantReference())) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => true,
                'waiting' => false,
                'redirectUrl' => $this->context->link->getPageLink(
                    'order-confirmation',
                    null,
                    null,
                    [
                        'id_cart' => $cart->id,
                        'id_module' => $this->module->id,
                        'key' => $customer->secure_key,
                    ]
                ),
            ]);
        }

        // Payment error, there is no order and order is not in the pending state
        if ($response->getPaymentTransaction()->getStatusCode()->isCanceledOrRejected()) {
            $this->dieOrderStep3();
        }

        OnlinePaymentsPrestaShopUtility::dieJsonArray([
            'success' => false,
            'message' => $this->module->l("Order for cart {$this->context->cart->id} not found.", 'payment'),
        ]);
    }

    public function displayAjaxRedirectReturnPaymentLinkInternalIframe()
    {
        $customer = new \Customer($this->context->customer->id);
        if (false === \Validate::isLoadedObject($customer)) {
            $this->dieOrderStep3();
        }

        $merchantReference = \Tools::getValue('merchantReference', '');

        $response = CheckoutAPI::get()
            ->payment((string)$this->context->shop->id)
            ->getPaymentOutcome(null, null, $merchantReference);

        if (!$response->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => false,
                'message' => $this->module->l('An error occurred while fetching the payment status.', 'payment'),
            ]);
        }

        $cart = new \Cart((int)$response->getPaymentTransaction()->getMerchantReference());
        if (
            false === \Validate::isLoadedObject($cart) ||
            (int)$cart->id_customer !== (int)$customer->id
        ) {
            $this->dieOrderStep3();
        }

        if ($response->isWaiting()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => true,
                'waiting' => true,
            ]);
        }

        if (false !== \Order::getIdByCartId((int)$response->getPaymentTransaction()->getMerchantReference())) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => true,
                'waiting' => false,
                'redirectUrl' => $this->context->link->getPageLink(
                    'order-confirmation',
                    null,
                    null,
                    [
                        'id_cart' => $cart->id,
                        'id_module' => $this->module->id,
                        'key' => $customer->secure_key,
                    ]
                ),
            ]);
        }

        // Payment error, there is no order and order is not in the pending state
        if ($response->getPaymentTransaction()->getStatusCode()->isCanceledOrRejected()) {
            $this->dieOrderStep3();
        }

        OnlinePaymentsPrestaShopUtility::dieJsonArray([
            'success' => false,
            'message' => $this->module->l("Order for cart {$this->context->cart->id} not found.", 'payment'),
        ]);
    }
}
