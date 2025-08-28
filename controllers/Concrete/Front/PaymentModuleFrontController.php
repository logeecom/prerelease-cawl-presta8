<?php

namespace OnlinePayments\Controllers\Concrete\Front;

use OnlinePayments\Classes\OnlinePaymentsModule;
use OnlinePayments\Classes\Services\Checkout\CartProviderWithDeviceData;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\Device;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentRequest;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Sdk\ResponseException;
use PrestaShop\Decimal\Number;
use WorldlineOP\PrestaShop\Repository\TokenRepository;
use WorldlineOP\PrestaShop\Utils\Tools;

/**
 * Class PaymentModuleFrontController.
 *
 * @package OnlinePayments\Controllers\Concrete\Front
 */
class PaymentModuleFrontController extends \ModuleFrontController
{
    public const MERCHANT_ACTION_REDIRECT = 'REDIRECT';

    public const TOKEN_STATUS_CREATED = 'CREATED';
    public const TOKEN_STATUS_UPDATED = 'UPDATED';

    /** @var OnlinePaymentsModule */
    public $module;

    /** @var \Monolog\Logger */
    public $logger;

    /**
     * @throws \Exception
     */
    public function displayAjaxCreatePayment()
    {
        $response = CheckoutAPI::get()
            ->hostedTokenization((string)$this->context->shop->id)
            ->pay(new PaymentRequest(
                (string)\Tools::getValue('hostedTokenizationId', ''),
                new CartProviderWithDeviceData(ServiceRegister::getService(CartProvider::class), $this->getDeviceData()),
                $this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectReturnIframe']),
                \Tools::getValue('tokenId', null)
            ));

        if (!$response->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => false,
                'message' => $this->module->l('An error occurred while processing the payment.', 'payment'),
            ]);
        }

        if ($response->isRedirectRequired()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => true,
                'needRedirect' => true,
                'redirectUrl' => $response->getRedirectUrl(),
            ]);
        }

        OnlinePaymentsPrestaShopUtility::dieJsonArray([
            'success' => true,
            'needRedirect' => true,
            'redirectUrl' => $this->context->link->getModuleLink(
                $this->module->name,
                'redirect',
                ['action' => 'redirectReturnInternalIframe', 'paymentId' => (string)$response->getPaymentTransaction()->getPaymentId()]
            ),
        ]);
    }

    public function displayAjaxCreatePaymentOld()
    {
        /** @var \WorldlineOP\PrestaShop\Logger\LoggerFactory $loggerFactory */
        $loggerFactory = $this->module->getService('worldlineop.logger.factory');
        $this->logger = $loggerFactory->setChannel('CreatePayment');

        $cart = $this->context->cart;
        $hostedTokenizationId = \Tools::getValue('hostedTokenizationId');
        $totalCartPost = new Number(\Tools::getValue('worldlineopTotalCartCents'));
        $cartCurrencyCodePost = \Tools::getValue('worldlineopCartCurrencyCode');
        $totalCart = Tools::getRoundedAmountInCents($cart->getOrderTotal(), Tools::getIsoCurrencyCodeById($cart->id_currency));
        $cartCurrencyCode = Tools::getIsoCurrencyCodeById($cart->id_currency);
        if ($totalCart !== $totalCartPost->getIntegerPart() || $cartCurrencyCode !== $cartCurrencyCodePost) {
            $this->logger->error(
                'Cart currency/amount does not match context',
                [
                    'cartCurrency' => $cartCurrencyCode,
                    'cartCurrencyPost' => $cartCurrencyCodePost,
                    'totalCart' => $totalCart,
                    'totalCartPost' => $totalCartPost->getIntegerPart(),
                ]
            );
            //@formatter:off
            die(json_encode([
                'success' => false,
                'message' => $this->module->l('An error occurred while processing the payment.', 'payment'),
            ]));
            //@formatter:on
        }

        /** @var \OnlinePayments\Sdk\Merchant\MerchantClient $merchantClient */
        $merchantClient = $this->module->getService('worldlineop.sdk.client');
        try {
            $hostedTokenizationResponse = $merchantClient->hostedTokenization()
                ->getHostedTokenization($hostedTokenizationId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['hostedTokenizationId' => $hostedTokenizationId]);
            //@formatter:off
            die(json_encode([
                'success' => false,
                'message' => $this->module->l('An error occurred while processing the payment.', 'payment'),
            ]));
            //@formatter:on
        }

        $this->logger->debug(
            'HostedTokenization Response',
            ['json' => json_decode($hostedTokenizationResponse->toJson(), true)]
        );
        $tokenId = $hostedTokenizationResponse->getToken()->getId();
        $ccForm = \Tools::getValue('ccForm');

        if (false === $hostedTokenizationResponse->getToken()->getIsTemporary() && (
                self::TOKEN_STATUS_CREATED === $hostedTokenizationResponse->getTokenStatus() ||
                self::TOKEN_STATUS_UPDATED === $hostedTokenizationResponse->getTokenStatus())
        ) {
            /** @var TokenRepository $tokenRepository */
            $tokenRepository = $this->module->getService('worldlineop.repository.token');
            $token = $tokenRepository->findByCustomerIdToken($this->context->customer->id, $tokenId);
            if (false === $token) {
                $token = new \WorldlineopToken();
            }
            $cardData = $hostedTokenizationResponse->getToken()->getCard()->getData()->getCardWithoutCvv();
            $token->id_customer = (int) $this->context->customer->id;
            $token->id_shop = (int) $this->context->shop->id;
            $token->product_id = PSQL($hostedTokenizationResponse->getToken()->getPaymentProductId());
            $token->card_number = pSQL($cardData->getCardNumber());
            $token->expiry_date = pSQL($cardData->getExpiryDate());
            $token->value = pSQL($tokenId);
            $token->secure_key = pSQL($this->context->customer->secure_key);
            $tokenRepository->save($token);
        }

        /** @var \WorldlineOP\PrestaShop\Builder\PaymentRequestDirector $hostedCheckoutDirector */
        $hostedCheckoutDirector = $this->module->getService('worldlineop.payment_request.director');
        try {
            $paymentRequest = $hostedCheckoutDirector->buildPaymentRequest($tokenId, $ccForm);
            $this->module->getLogger()->debug('IframeHostedTokenizationRequest', ['json' => json_decode($paymentRequest->toJson(), true)]);
            $paymentResponse = $merchantClient->payments()
                ->createPayment($paymentRequest);
            $this->logger->debug('IframeHostedTokenizationResponse', ['json' => json_decode($paymentResponse->toJson(), true)]);
        } catch (ResponseException $re) {
            $this->logger->debug('IframeHostedTokenizationResponse', ['json' => json_decode($re->getResponse()->toJson(), true)]);
            //@formatter:off
            die(json_encode([
                'success' => false,
                'message' => $this->module->l('An error occurred while processing the payment.', 'payment'),
            ]));
            //@formatter:on
        } catch (\Exception $e) {
            $this->logger->debug('IframeHostedTokenizationResponse', ['json' => json_decode($e->getResponse()->toJson(), true)]);
            //@formatter:off
            die(json_encode([
                'success' => false,
                'message' => $this->module->l('An error occurred while processing the payment.', 'payment'),
            ]));
            //@formatter:on
        }
        /** @var \WorldlineOP\PrestaShop\Repository\CreatedPaymentRepository $createdPaymentRepository */
        $createdPaymentRepository = $this->module->getService('worldlineop.repository.created_payment');
        $this->logger->debug('Payment Response', ['response' => json_decode($paymentResponse->toJson(), true)]);
        $createdPayment = new \CreatedPayment();
        $createdPayment->id_cart = (int) $cart->id;
        $createdPayment->payment_id = pSQL($paymentResponse->getPayment()->getId());
        $createdPayment->merchant_reference = pSQL($paymentResponse->getPayment()->getPaymentOutput()->getReferences()
            ->getMerchantReference());
        $createdPayment->status = pSQL($paymentResponse->getPayment()->getStatus());
        $merchantAction = $paymentResponse->getMerchantAction();
        if (null !== $merchantAction && $merchantAction->getActionType() === self::MERCHANT_ACTION_REDIRECT) {
            $createdPayment->returnmac = pSQL($merchantAction->getRedirectData()->getRETURNMAC());
            $return = [
                'success' => true,
                'needRedirect' => true,
                'redirectUrl' => $merchantAction->getRedirectData()->getRedirectURL(),
            ];
        } else {
            $return = [
                'success' => true,
                'needRedirect' => true,
                'redirectUrl' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'redirect',
                    ['action' => 'redirectReturnInternalIframe', 'paymentId' => $createdPayment->payment_id]
                ),
            ];
        }
        try {
            $createdPaymentRepository->save($createdPayment);
        } catch (\Exception $e) {
            $this->logger->error('Cannot save CreatedPayment object', ['message' => $e->getMessage()]);
            //@formatter:off
            $return = [
                'success' => false,
                'message' => $this->module->l('An unexpected error occurred. Please contact our customer service.', 'payment'),
            ];
            //@formatter:on
        }
        die(json_encode($return));
    }

    /**
     * @return void
     */
    public function displayAjaxFormatSurchargeAmounts()
    {
        try {
            $return = [
                'success' => true,
                'formattedInitialAmount' => Tools::getRoundedAmountFromCents(
                    \Tools::getValue('initialAmount'),
                    \Tools::getValue('initialCurrency')
                    ) . ' ' . \Tools::getValue('initialCurrency'),
                'formattedSurchargeAmount' => Tools::getRoundedAmountFromCents(
                    \Tools::getValue('surchargeAmount'),
                    \Tools::getValue('surchargeCurrency')
                    ) . ' ' . \Tools::getValue('surchargeCurrency'),
                'formattedTotalAmount' => Tools::getRoundedAmountFromCents(
                    \Tools::getValue('totalAmount'),
                    \Tools::getValue('totalCurrency'
                    )) . ' ' . \Tools::getValue('totalCurrency'),
            ];
        } catch (\Exception $e) {
            $return = [
                'success' => false,
            ];
        }

        die(json_encode($return));
    }

    private function getDeviceData(): ?Device
    {
        $ccForm = \Tools::getValue('ccForm');
        if (empty($ccForm)) {
            return null;
        }

        $ipaddress = $_SERVER['REMOTE_ADDR'];
        $customerConnections = $this->context->customer->getLastConnections();
        if (!empty($customerConnections)) {
            $connection = $customerConnections[0];
            $ipaddress =  $connection['ipaddress'];
        }

        return new Device(
            $_SERVER['HTTP_ACCEPT'],
            $_SERVER['HTTP_USER_AGENT'],
            $ipaddress,
            (int)$ccForm['colorDepth'],
            (string)$ccForm['screenHeight'],
            (string)$ccForm['screenWidth'],
            (string)$ccForm['timezoneOffsetUtcMinutes'],
            (bool)$ccForm['javaEnabled'],
        );
    }
}
