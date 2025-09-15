<?php

namespace CAWL\OnlinePayments\Classes\Services\Checkout;

use Context;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Classes\Utility\Tools;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\GeneralSettingsService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Cards\FlowType;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\CreditCard;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedTokenization\ValidTokensResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod\PaymentMethodService;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
/**
 * Class PaymentOptionsService.
 *
 * @package OnlinePayments\Classes\Services\Checkout
 */
class PaymentOptionsService
{
    private OnlinePaymentsModule $module;
    private Context $context;
    private CartProvider $cartProvider;
    public function __construct(OnlinePaymentsModule $module, Context $context, CartProvider $cartProvider)
    {
        $this->module = $module;
        $this->context = $context;
        $this->cartProvider = $cartProvider;
    }
    public function getAvailable() : array
    {
        $availableMMethodsResponse = CheckoutAPI::get()->paymentMethods((string) $this->context->shop->id)->getAvailablePaymentMethods($this->cartProvider);
        if (!$availableMMethodsResponse->isSuccessful()) {
            return [];
        }
        $locale = \Language::getLocaleByIso(\Language::getIsoById($this->context->cart->id_lang));
        $locale = \strtoupper(\explode('-', $locale)[0]);
        return \array_merge($this->getStoredTokensOptions($availableMMethodsResponse->getPaymentMethods(), $availableMMethodsResponse->getValidTokensResponse(), $locale), $this->getUngroupedHostedTokenizationOptions($availableMMethodsResponse->getPaymentMethods(), $locale), $this->getGroupedHostedTokenizationOptions($availableMMethodsResponse->getPaymentMethods(), $locale), $this->getHostedCheckoutOptions($availableMMethodsResponse->getPaymentMethods(), $locale), $this->getRedirectOptions($availableMMethodsResponse->getPaymentMethods(), $locale));
    }
    /**
     * @param PaymentMethodCollection $availableMethods
     * @param ValidTokensResponse|null $validTokensResponse
     * @param string $locale
     *
     * @return PaymentOption[]
     */
    private function getStoredTokensOptions(PaymentMethodCollection $availableMethods, ?ValidTokensResponse $validTokensResponse, string $locale) : array
    {
        if (null === $validTokensResponse) {
            return [];
        }
        $result = [];
        foreach ($validTokensResponse->getTokens() as $token) {
            if ($availableMethods->has(PaymentProductId::cards())) {
                $method = $availableMethods->get(PaymentProductId::cards());
                $result[] = $this->getStoredHostedTokenizationTokenOptions($token, $validTokensResponse->getHostedTokenization(), $method, $locale);
                continue;
            }
            if ($availableMethods->has(PaymentProductId::hostedCheckout())) {
                $result[] = $this->getStoredHostedCheckoutTokenOptions($token);
            }
        }
        $this->context->smarty->assign('tokenHTP', \array_map(function (Token $token) {
            return ['id' => $token->getTokenId()];
        }, $validTokensResponse->getTokens()));
        return $result;
    }
    private function getStoredHostedTokenizationTokenOptions(Token $token, HostedTokenization $hostedTokenization, PaymentMethod $paymentMethod, string $locale) : PaymentOption
    {
        /** @var GeneralSettingsService $settingsService */
        $settingsService = ServiceRegister::getService(GeneralSettingsService::class);
        $paymentSettings = StoreContext::doWithStore((string) $this->context->shop->id, function () use($settingsService) {
            return $settingsService->getPaymentSettings();
        });
        $amount = Amount::fromFloat($this->context->cart->getOrderTotal(), Currency::fromIsoCode(Tools::getIsoCurrencyCodeById((int) $this->context->cart->id_currency)));
        $tokenSurcharge = [];
        $surcharge = \false;
        if ($paymentSettings->isApplySurcharge()) {
            $surchargeRequest = new SurchargeRequest($amount, $token->getTokenId());
            /** @var PaymentMethodService $paymentService */
            $paymentService = ServiceRegister::getService(PaymentMethodService::class);
            $surcharge = StoreContext::doWithStore((string) $this->context->shop->id, function () use($surchargeRequest, $paymentService) {
                return $paymentService->calculateSurcharge($surchargeRequest);
            });
        }
        if ($surcharge) {
            $tokenSurcharge = ['amountWithoutSurcharge' => $surcharge->getNetAmount()->getPriceInCurrencyUnits(), 'amountWithSurcharge' => $surcharge->getTotalAmount()->getPriceInCurrencyUnits(), 'surchargeAmount' => $surcharge->getSurchargeAmount()->getPriceInCurrencyUnits(), 'currencyIso' => $surcharge->getNetAmount()->getCurrency()->getIsoCode()];
        }
        $createPaymentUrl = $this->context->link->getModuleLink($this->module->name, 'payment');
        $this->context->smarty->assign(['module' => $this->module->name, 'tokenId' => $token->getTokenId(), 'tokenSurcharge' => $tokenSurcharge, 'hostedTokenizationPageUrl' => $hostedTokenization->getUrl(), 'createPaymentUrl' => $createPaymentUrl, 'cardToken' => $token->getTokenId(), 'totalCartCents' => $amount->getValue(), 'cartCurrencyCode' => Tools::getIsoCurrencyCodeById((int) $this->context->cart->id_currency), 'customerToken' => \Tools::getToken(), 'surchargeEnabled' => $paymentSettings->isApplySurcharge()]);
        $paymentOption = new PaymentOption();
        /** @var TranslationCollection $vaultTitles */
        $vaultTitles = $paymentMethod->getAdditionalData()->getVaultTitles();
        $vaultName = $vaultTitles->getTranslation($locale)->getMessage() ?: $vaultTitles->getDefaultTranslation()->getMessage();
        $paymentOption->setCallToActionText($vaultName . ' ' . $token->getCardNumber())->setAdditionalInformation($this->context->smarty->fetch("module:{$this->module->name}/views/templates/front/hostedTokenizationAdditionalInformation_1click.tpl"))->setBinary(\true)->setLogo(\sprintf($this->module->getPathUri() . 'views/assets/images/payment_products/%s.svg', (string) $token->getProductId()))->setModuleName($this->module->name . '-token-htp-' . $token->getTokenId());
        return $paymentOption;
    }
    private function getStoredHostedCheckoutTokenOptions(Token $token) : PaymentOption
    {
        $paymentOption = new PaymentOption();
        $paymentOption->setAction($this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectExternal', 'ajax' => \true, 'productId' => $token->getProductId(), 'tokenId' => $token->getTokenId()]))->setLogo(\sprintf($this->module->getPathUri() . 'views/assets/images/payment_products/%s.svg', (string) $token->getProductId()))->setCallToActionText(\sprintf($this->module->l('Pay with my previously saved card %s', 'PaymentOptionsPresenter'), $token->getCardNumber()));
        return $paymentOption;
    }
    private function getUngroupedHostedTokenizationOptions(PaymentMethodCollection $availableMethods, string $locale) : array
    {
        return [];
    }
    /**
     * @param PaymentMethodCollection $availableMethods
     * @param string $locale
     * @return PaymentOption[]
     */
    private function getGroupedHostedTokenizationOptions(PaymentMethodCollection $availableMethods, string $locale) : array
    {
        $cardsPaymentMethod = $availableMethods->get(PaymentProductId::cards());
        if (!$cardsPaymentMethod) {
            return [];
        }
        /** @var CreditCard|null $cardAdditionalData */
        $cardAdditionalData = $cardsPaymentMethod->getAdditionalData();
        if (!$cardAdditionalData || !$cardAdditionalData->isEnableGroupCards()) {
            return [];
        }
        if ($cardAdditionalData->getType()->equals(FlowType::iframe())) {
            return [$this->getGroupedCardsHostedTokenizationOption($cardsPaymentMethod->getName()->getTranslationMessage($locale))];
        }
        return [$this->getGroupedCardsHostedCheckoutOption($cardsPaymentMethod->getName()->getTranslationMessage($locale))];
    }
    private function getGroupedCardsHostedTokenizationOption(string $callToActionLabel) : ?PaymentOption
    {
        $hostedTokenizationResponse = CheckoutAPI::get()->hostedTokenization((string) $this->context->shop->id)->crate($this->cartProvider);
        if (!$hostedTokenizationResponse->isSuccessful()) {
            return null;
        }
        /** @var GeneralSettingsService $settingsService */
        $settingsService = ServiceRegister::getService(GeneralSettingsService::class);
        $paymentSettings = StoreContext::doWithStore((string) $this->context->shop->id, function () use($settingsService) {
            return $settingsService->getPaymentSettings();
        });
        $redirectUrl = $hostedTokenizationResponse->getHostedTokenization()->getUrl();
        $createPaymentUrl = $this->context->link->getModuleLink($this->module->name, 'payment');
        $this->context->smarty->assign(['module' => $this->module->name, 'displayHTP' => \true, 'hostedTokenizationPageUrl' => $redirectUrl, 'createPaymentUrl' => $createPaymentUrl, 'totalCartCents' => Amount::fromFloat($this->context->cart->getOrderTotal(), Currency::fromIsoCode(Tools::getIsoCurrencyCodeById((int) $this->context->cart->id_currency)))->getValue(), 'cartCurrencyCode' => Tools::getIsoCurrencyCodeById((int) $this->context->cart->id_currency), 'customerToken' => \Tools::getToken(), 'surchargeEnabled' => $paymentSettings->isApplySurcharge()]);
        $paymentOption = new PaymentOption();
        $paymentOption->setCallToActionText($callToActionLabel)->setAdditionalInformation($this->context->smarty->fetch("module:{$this->module->name}/views/templates/front/hostedTokenizationAdditionalInformation.tpl"))->setBinary(\true)->setLogo($this->module->getPathUri() . 'views/assets/images/payment_products/cb_visa_mc_amex.svg')->setModuleName($this->module->name . '-htp');
        return $paymentOption;
    }
    private function getGroupedCardsHostedCheckoutOption(string $callToActionLabel) : ?PaymentOption
    {
        $cardsProductId = (string) PaymentProductId::cards();
        $paymentOption = new PaymentOption();
        $paymentOption->setAction($this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectExternal', 'ajax' => \true, 'productId' => (string) PaymentProductId::cards()]))->setLogo($this->module->getPathUri() . 'views/assets/images/payment_products/cb_visa_mc_amex.svg')->setCallToActionText($callToActionLabel);
        return $paymentOption;
    }
    /**
     * @param PaymentMethodCollection $availableMethods
     * @param string $locale
     * @return PaymentOption[]
     */
    private function getHostedCheckoutOptions(PaymentMethodCollection $availableMethods, string $locale) : array
    {
        if (!$availableMethods->has(PaymentProductId::hostedCheckout())) {
            return [];
        }
        $method = null;
        foreach ($availableMethods->toArray() as $availableMethod) {
            if (PaymentProductId::hostedCheckout()->equals($availableMethod->getProductId())) {
                $method = $availableMethod;
            }
        }
        $paymentOption = new PaymentOption();
        $paymentOption->setAction($this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectExternal', 'ajax' => \true]))->setLogo($method->getAdditionalData()->getLogo())->setCallToActionText($availableMethods->get(PaymentProductId::hostedCheckout())->getName()->getTranslationMessage($locale));
        return [$paymentOption];
    }
    /**
     * @param PaymentMethodCollection $getPaymentMethods
     * @param string $locale
     * @return PaymentOption[]
     */
    private function getRedirectOptions(PaymentMethodCollection $getPaymentMethods, string $locale) : array
    {
        $result = [];
        foreach ($getPaymentMethods->toArray() as $paymentMethod) {
            if ($paymentMethod->getProductId()->equals(PaymentProductId::cards()) || $paymentMethod->getProductId()->equals(PaymentProductId::hostedCheckout())) {
                continue;
            }
            $paymentOption = new PaymentOption();
            $paymentOption->setAction($this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectExternal', 'ajax' => \true, 'productId' => (string) $paymentMethod->getProductId()]))->setLogo(\sprintf($this->module->getPathUri() . 'views/assets/images/payment_products/%s.svg', (string) $paymentMethod->getProductId()))->setCallToActionText($paymentMethod->getName()->getTranslationMessage($locale));
            $result[] = $paymentOption;
        }
        return $result;
    }
}
