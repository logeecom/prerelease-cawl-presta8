<?php

namespace OnlinePayments\Classes\Services\Checkout;

use Context;
use OnlinePayments\Classes\OnlinePaymentsModule;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\GeneralSettingsService;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedTokenization\ValidTokensResponse;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod\PaymentMethodService;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use WorldlineOP\PrestaShop\Utils\Tools;

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

    public function getAvailable(): array
    {
        $availableMMethodsResponse = CheckoutAPI::get()
            ->paymentMethods((string)$this->context->shop->id)
            ->getAvailablePaymentMethods($this->cartProvider);

        if (!$availableMMethodsResponse->isSuccessful()) {
            return [];
        }

        $locale = \Language::getLocaleByIso(\Language::getIsoById($this->context->cart->id_lang));
        return array_merge(
            $this->getStoredTokensOptions(
                $availableMMethodsResponse->getPaymentMethods(),
                $availableMMethodsResponse->getValidTokensResponse()
            ),
            $this->getHostedTokenizationOptions($availableMMethodsResponse->getPaymentMethods(), $locale),
            $this->getHostedCheckoutOptions($availableMMethodsResponse->getPaymentMethods(), $locale),
            $this->getRedirectOptions($availableMMethodsResponse->getPaymentMethods(), $locale),
        );
    }

    /**
     * @param PaymentMethodCollection $availableMethods
     * @param Token[] $tokens
     * @return PaymentOption[]
     */
    private function getStoredTokensOptions(
        PaymentMethodCollection $availableMethods,
        ?ValidTokensResponse $validTokensResponse
    ): array {
        if (null === $validTokensResponse) {
            return [];
        }

        $result = [];
        foreach ($validTokensResponse->getTokens() as $token) {
            if ($availableMethods->has(PaymentProductId::cards())) {
                $method = $availableMethods->get(PaymentProductId::cards());
                $result[] = $this->getStoredHostedTokenizationTokenOptions(
                    $token, $validTokensResponse->getHostedTokenization(), $method
                );

                continue;
            }

            if ($availableMethods->has(PaymentProductId::hostedCheckout())) {
                $result[] = $this->getStoredHostedCheckoutTokenOptions($token);
            }
        }

        $this->context->smarty->assign('tokenHTP', array_map(function (Token $token) {
            return ['id' => $token->getTokenId()];
        }, $validTokensResponse->getTokens()));

        return $result;
    }

    private function getStoredHostedTokenizationTokenOptions(
        Token $token,
        HostedTokenization $hostedTokenization,
        PaymentMethod $paymentMethod
    ): PaymentOption {
        /** @var GeneralSettingsService $settingsService */
        $settingsService = ServiceRegister::getService(GeneralSettingsService::class);
        $paymentSettings = StoreContext::doWithStore((string)$this->context->shop->id, function () use ($settingsService) {
            return $settingsService->getPaymentSettings();
        });

        $amount = Amount::fromFloat($this->context->cart->getOrderTotal(),
            Currency::fromIsoCode(Tools::getIsoCurrencyCodeById($this->context->cart->id_currency)));

        $tokenSurcharge = [];
        $surcharge = false;

        if ($paymentSettings->isApplySurcharge()) {
            $surchargeRequest = new SurchargeRequest($amount, $token->getTokenId());
            /** @var PaymentMethodService $paymentService */
            $paymentService = ServiceRegister::getService(PaymentMethodService::class);

            $surcharge = StoreContext::doWithStore((string)$this->context->shop->id,
                function () use ($surchargeRequest, $paymentService) {
                    return $paymentService->calculateSurcharge($surchargeRequest);
                }
            );
        }

        if ($surcharge) {
            $tokenSurcharge = [
                'amountWithoutSurcharge' => $surcharge->getNetAmount()->getPriceInCurrencyUnits(),
                'amountWithSurcharge' => $surcharge->getTotalAmount()->getPriceInCurrencyUnits(),
                'surchargeAmount' => $surcharge->getSurchargeAmount()->getPriceInCurrencyUnits(),
                'currencyIso' => $surcharge->getNetAmount()->getCurrency()->getIsoCode(),
            ];
        }

        $createPaymentUrl = $this->context->link->getModuleLink($this->module->name, 'payment');
        $this->context->smarty->assign([
            'tokenId' => $token->getTokenId(),
            'tokenSurcharge' => $tokenSurcharge,
            'hostedTokenizationPageUrl' => $hostedTokenization->getUrl(),
            'createPaymentUrl' => $createPaymentUrl,
            'cardToken' => $token->getTokenId(),
            'totalCartCents' => $amount->getValue(),
            'cartCurrencyCode' => Tools::getIsoCurrencyCodeById($this->context->cart->id_currency),
            'worldlineopCustomerToken' => \Tools::getToken(),
            'surchargeEnabled' => $paymentSettings->isApplySurcharge(),
        ]);

        $paymentOption = new PaymentOption();
        /** @var TranslationCollection $vaultTitles */
        $vaultTitles = $paymentMethod->getAdditionalData()->getVaultTitles();
        $vaultName = $vaultTitles->getTranslation($this->context->language->iso_code)->getMessage() ?: $vaultTitles->getDefaultTranslation()->getMessage();
        $paymentOption
            ->setCallToActionText(sprintf($vaultName, $token->getCardNumber()))
            ->setAdditionalInformation($this->context->smarty->fetch(
                'module:worldlineop/views/templates/front/hostedTokenizationAdditionalInformation_1click.tpl'
            ))
            ->setBinary(true)
            ->setLogo(sprintf($this->module->getPathUri() . 'views/assets/images/payment_products/%s.svg',
                (string)$token->getProductId()))
            ->setModuleName('worldlineop-token-htp-' . $token->getTokenId());

        return $paymentOption;
    }

    private function getStoredHostedCheckoutTokenOptions(Token $token): PaymentOption
    {
        $paymentOption = new PaymentOption();
        $paymentOption
            ->setAction($this->context->link->getModuleLink($this->module->name, 'redirect', [
                'action' => 'redirectExternal',
                'ajax' => true,
                'productId' => $token->getProductId(),
                'tokenId' => $token->getTokenId()
            ]))
            ->setLogo(sprintf($this->module->getPathUri() . 'views/assets/images/payment_products/%s.svg',
                (string)$token->getProductId()))
            ->setCallToActionText(sprintf($this->module->l('Pay with my previously saved card %s',
                'PaymentOptionsPresenter'), $token->getCardNumber()));

        return $paymentOption;
    }

    /**
     * @param PaymentMethodCollection $availableMethods
     * @param string $locale
     * @return PaymentOption[]
     */
    private function getHostedTokenizationOptions(PaymentMethodCollection $availableMethods, string $locale): array
    {
        if (!$availableMethods->has(PaymentProductId::cards())) {
            return [];
        }

        $hostedTokenizationResponse = CheckoutAPI::get()
            ->hostedTokenization((string)$this->context->shop->id)
            ->crate($this->cartProvider);

        if (!$hostedTokenizationResponse->isSuccessful()) {
            return [];
        }

        /** @var GeneralSettingsService $settingsService */
        $settingsService = ServiceRegister::getService(GeneralSettingsService::class);
        $paymentSettings = StoreContext::doWithStore((string)$this->context->shop->id, function () use ($settingsService) {
            return $settingsService->getPaymentSettings();
        });

        $redirectUrl = $hostedTokenizationResponse->getHostedTokenization()->getUrl();
        $createPaymentUrl = $this->context->link->getModuleLink($this->module->name, 'payment');
        $this->context->smarty->assign([
            'displayHTP' => true,
            'hostedTokenizationPageUrl' => $redirectUrl,
            'createPaymentUrl' => $createPaymentUrl,
            'totalCartCents' => Amount::fromFloat(
                $this->context->cart->getOrderTotal(),
                Currency::fromIsoCode(Tools::getIsoCurrencyCodeById($this->context->cart->id_currency))
            )->getValue(),
            'cartCurrencyCode' => Tools::getIsoCurrencyCodeById($this->context->cart->id_currency),
            'worldlineopCustomerToken' => \Tools::getToken(),
            'surchargeEnabled' => $paymentSettings->isApplySurcharge(),
        ]);

        $paymentOption = new PaymentOption();
        $paymentOption
            ->setCallToActionText(
                $availableMethods->get(PaymentProductId::cards())->getName()->getTranslationMessage($locale)
            )
            ->setAdditionalInformation($this->context->smarty->fetch(
                "module:{$this->module->name}/views/templates/front/hostedTokenizationAdditionalInformation.tpl"
            ))
            ->setBinary(true)
            ->setLogo($this->module->getPathUri() . 'views/assets/images/payment_products/cb_visa_mc_amex.svg')
            ->setModuleName('worldlineop-htp');

        return [$paymentOption];
    }

    /**
     * @param PaymentMethodCollection $availableMethods
     * @param string $locale
     * @return PaymentOption[]
     */
    private function getHostedCheckoutOptions(PaymentMethodCollection $availableMethods, string $locale): array
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
        $paymentOption
            ->setAction($this->context->link->getModuleLink(
                $this->module->name, 'redirect',
                ['action' => 'redirectExternal', 'ajax' => true])
            )
            ->setLogo($method->getAdditionalData()->getLogo())
            ->setCallToActionText(
                $availableMethods->get(PaymentProductId::hostedCheckout())->getName()->getTranslationMessage($locale)
            );

        return [$paymentOption];
    }

    /**
     * @param PaymentMethodCollection $getPaymentMethods
     * @param string $locale
     * @return PaymentOption[]
     */
    private function getRedirectOptions(PaymentMethodCollection $getPaymentMethods, string $locale): array
    {
        $result = [];
        foreach ($getPaymentMethods->toArray() as $paymentMethod) {
            if (
                $paymentMethod->getProductId()->equals(PaymentProductId::cards()) ||
                $paymentMethod->getProductId()->equals(PaymentProductId::hostedCheckout())
            ) {
                continue;
            }

            $paymentOption = new PaymentOption();
            $paymentOption
                ->setAction($this->context->link->getModuleLink($this->module->name, 'redirect', [
                    'action' => 'redirectExternal',
                    'ajax' => true,
                    'productId' => (string)$paymentMethod->getProductId()
                ]))
                ->setLogo(sprintf($this->module->getPathUri() . 'views/assets/images/payment_products/%s.svg',
                    (string)$paymentMethod->getProductId()))
                ->setCallToActionText($paymentMethod->getName()->getTranslationMessage($locale));

            $result[] = $paymentOption;
        }

        return $result;
    }

}