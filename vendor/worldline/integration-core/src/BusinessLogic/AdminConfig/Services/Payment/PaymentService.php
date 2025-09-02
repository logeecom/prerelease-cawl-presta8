<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Payment;

use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo\LogoUrlService;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidPaymentProductIdException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidSessionTimeoutException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\BankTransfer;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\CreditCard;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\HostedCheckout;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Intersolve;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Oney;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\PaymentMethodAdditionalData;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Sepa;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodDefaultConfigs;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodResponse;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories\PaymentConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;

/**
 * Class PaymentService
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Payment
 */
class PaymentService
{
    public const CREDIT_CARD_VAULT_TITLES = [
        'EN' => 'Saved credit card',
        'DE' => 'Kreditkarte gespeichert',
        'FR' => 'Carte de crédit enregistrée',
        'ES' => 'Tarjeta de crédito guardada',
        'IT' => 'Carta di credito salvata'
    ];
    protected PaymentConfigRepositoryInterface $repository;
    protected LogoUrlService $logoUrlService;
    protected ActiveBrandProviderInterface $activeBrandProvider;

    /**
     * @param PaymentConfigRepositoryInterface $repository
     * @param LogoUrlService $logoUrlService
     * @param ActiveBrandProviderInterface $activeBrandProvider
     */
    public function __construct(PaymentConfigRepositoryInterface $repository, LogoUrlService $logoUrlService, ActiveBrandProviderInterface $activeBrandProvider)
    {
        $this->repository = $repository;
        $this->logoUrlService = $logoUrlService;
        $this->activeBrandProvider = $activeBrandProvider;
    }

    /**
     * Retrieves payment methods configurations.
     *
     * @return PaymentMethodResponse[]
     */
    public function getPaymentMethods(): array
    {
        $defaultMethods = $this->getSupportedPaymentMethods();
        $configuredMethods = $this->repository->getPaymentMethods();

        return $this->transformToResponse($configuredMethods->union($defaultMethods));
    }

    /**
     * Saves payment method configuration.
     *
     * @param PaymentMethod $paymentMethod
     *
     * @return void
     */
    public function savePaymentMethod(PaymentMethod $paymentMethod): void
    {
        if (PaymentProductId::hostedCheckout()->equals($paymentMethod->getProductId())
            && empty($paymentMethod->getAdditionalData()->getLogo())) {
            $paymentMethod->getAdditionalData()->setLogo($this->logoUrlService->getHostedCheckoutLogoUrl());
        }

        $this->repository->savePaymentMethod($paymentMethod);
    }

    /**
     * @param string $productId
     *
     * @return PaymentMethod|null
     *
     * @throws InvalidPaymentProductIdException
     * @throws InvalidSessionTimeoutException
     */
    public function getPaymentMethod(string $productId): ?PaymentMethod
    {
        $method = $this->repository->getPaymentMethod($productId);

        if (!$method) {
            $method = $this->getDefaultPaymentMethodConfig($productId);
        }

        return $method;
    }

    /**
     * @param string $productId
     * @param bool $enabled
     *
     * @return void
     *
     * @throws InvalidPaymentProductIdException
     * @throws InvalidSessionTimeoutException
     */
    public function enablePaymentMethod(string $productId, bool $enabled): void
    {
        $method = $this->repository->getPaymentMethod($productId);

        if (!$method) {
            $method = $this->getDefaultPaymentMethodConfig((string)$productId);
        }

        $this->repository->savePaymentMethod(
            new PaymentMethod(
                $method->getProductId(),
                $method->getName(),
                $enabled,
                $method->getTemplate(),
                $method->getAdditionalData()
            )
        );
    }

    /**
     * @param PaymentMethodCollection $collection
     *
     * @return PaymentMethodResponse[]
     */
    protected function transformToResponse(PaymentMethodCollection $collection): array
    {
        $result = [];

        foreach ($collection->toArray() as $paymentMethod) {
            $result[] = new PaymentMethodResponse(
                $paymentMethod->getProductId()->getId(),
                $paymentMethod->getName(),
                PaymentMethodDefaultConfigs::getPaymentGroup($paymentMethod->getProductId()->getId()),
                PaymentMethodDefaultConfigs::getIntegrationTypes($paymentMethod->getProductId()->getId()),
                $paymentMethod->isEnabled()
            );
        }

        return $result;
    }

    protected function getSupportedPaymentMethods(): PaymentMethodCollection
    {
        $methods = [];

        foreach ($this->getSupportedPaymentProducts() as $paymentProductId) {
            $methods[] = $this->getDefaultPaymentMethodConfig($paymentProductId);
        }

        return new PaymentMethodCollection($methods);
    }

    protected function getSupportedPaymentProducts(): array
    {
        return PaymentProductId::SUPPORTED_PAYMENT_PRODUCTS;
    }

    /**
     * @param string $paymentProductId
     *
     * @return PaymentMethod
     *
     * @throws InvalidPaymentProductIdException
     * @throws InvalidSessionTimeoutException
     */
    protected function getDefaultPaymentMethodConfig(string $paymentProductId): PaymentMethod
    {
        $defaultName = PaymentMethodDefaultConfigs::getName(
            $paymentProductId,
            $this->activeBrandProvider->getActiveBrand()->getPaymentMethodName()
        );
        $name = new Translation($defaultName['language'], $defaultName['translation']);

        return new PaymentMethod(
            PaymentProductId::parse($paymentProductId),
            new TranslationCollection($name),
            false,
            '',
            $this->getAdditionalData($paymentProductId)
        );
    }

    /**
     * @throws InvalidSessionTimeoutException
     * @throws InvalidPaymentProductIdException
     */
    protected function getAdditionalData(string $paymentProductId): ?PaymentMethodAdditionalData
    {
        if (PaymentProductId::bankTransfer()->equals($paymentProductId)) {
            return new BankTransfer(false);
        }

        if (PaymentProductId::cards()->equals($paymentProductId)) {
            return new CreditCard(
                $this->getCreditCardVaultTitles(),
            );
        }

        if (PaymentProductId::hostedCheckout()->equals($paymentProductId)) {
            return new HostedCheckout(
                $this->logoUrlService->getHostedCheckoutLogoUrl(),
                true
            );
        }

        if (PaymentProductId::intersolve()->equals($paymentProductId)) {
            return new Intersolve(new Intersolve\SessionTimeout(180), Intersolve\PaymentProductId::parse('5700'));
        }

        if (PaymentProductId::oney3x()->equals($paymentProductId) ||
            PaymentProductId::oney4x()->equals($paymentProductId) ||
            PaymentProductId::oneyFinancementLong()->equals($paymentProductId) ||
            PaymentProductId::oneyBrandedGiftCard()->equals($paymentProductId) ||
            PaymentProductId::oneyBankCard()->equals($paymentProductId)) {
            return new Oney('');
        }

        if (PaymentProductId::sepaDirectDebit()->equals($paymentProductId)) {
            return new Sepa(Sepa\RecurrenceType::unique(), Sepa\SignatureType::sms());
        }

        return null;
    }

    protected function getCreditCardVaultTitles(): TranslationCollection
    {
        $default = new Translation('EN', self::CREDIT_CARD_VAULT_TITLES['EN']);
        $vaultTitleCollection = new TranslationCollection($default);

        foreach (self::CREDIT_CARD_VAULT_TITLES as $lang => $title) {
            $vaultTitleCollection->addTranslation(new Translation($lang, $title));
        }

        return $vaultTitleCollection;
    }
}
