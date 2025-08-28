<?php

namespace OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod;

use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidPaymentProductIdException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidRecurrenceTypeException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidSessionTimeoutException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidSignatureTypeException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\BankTransfer;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\CreditCard;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\HostedCheckout;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Intersolve;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Oney;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\PaymentMethodAdditionalData;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Sepa;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use OnlinePayments\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use OnlinePayments\Core\Infrastructure\ORM\Configuration\IndexMap;
use OnlinePayments\Core\Infrastructure\ORM\Entity;

/**
 * Class PaymentMethodConfigEntity.
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod
 */
class PaymentMethodConfigEntity extends Entity
{
    public const CLASS_NAME = __CLASS__;

    protected string $storeId;
    protected string $mode;
    protected bool $enabled;
    protected string $paymentProductId;
    protected PaymentMethod $paymentMethod;

    public function getConfig(): EntityConfiguration
    {
        $indexMap = new IndexMap();

        $indexMap->addStringIndex('storeId');
        $indexMap->addStringIndex('mode');
        $indexMap->addBooleanIndex('enabled');
        $indexMap->addStringIndex('paymentProductId');

        return new EntityConfiguration($indexMap, 'PaymentMethodConfig');
    }

    public function inflate(array $data): void
    {
        parent::inflate($data);

        $this->storeId = $data['storeId'];
        $this->mode = $data['mode'];
        $this->enabled = $data['enabled'];
        $this->paymentProductId = $data['paymentProductId'];

        $paymentMethod = $data['paymentMethod'] ?? [];
        $firstTranslation = $paymentMethod['nameTranslations'][0];
        $nameTranslations = new TranslationCollection(new Translation($firstTranslation['language'], $firstTranslation['translation']));
        unset($paymentMethod['nameTranslations'][0]);

        foreach ($paymentMethod['nameTranslations'] as $translation) {
            $nameTranslations->addTranslation(new Translation(
                $translation['language'],
                $translation['translation']
            ));
        }

        $this->paymentMethod = new PaymentMethod(
            PaymentProductId::parse($paymentMethod['paymentProductId']),
            $nameTranslations,
            $paymentMethod['enabled'] ?? false,
            $paymentMethod['template'] ?? '',
            $this->additionalDataFromArray($paymentMethod)
        );
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['storeId'] = $this->storeId;
        $data['mode'] = $this->mode;
        $data['enabled'] = $this->enabled;
        $data['paymentProductId'] = $this->paymentProductId;

        $nameTranslations = [];

        foreach ($this->paymentMethod->getName()->getTranslations() as $item) {
            $nameTranslations[] = [
                'language' => $item->getLocaleCode(),
                'translation' => $item->getMessage(),
            ];
        }

        $data['paymentMethod'] = [
            'paymentProductId' => (string)$this->paymentMethod->getProductId(),
            'nameTranslations' => $nameTranslations,
            'enabled' => $this->paymentMethod->isEnabled(),
            'template' => $this->paymentMethod->getTemplate(),
            'additionalData' => $this->additionalDataToArray(),
        ];

        return $data;
    }

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    public function setStoreId(string $storeId): void
    {
        $this->storeId = $storeId;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getPaymentProductId(): string
    {
        return $this->paymentProductId;
    }

    public function setPaymentProductId(string $paymentProductId): void
    {
        $this->paymentProductId = $paymentProductId;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @param array $data
     *
     * @return PaymentMethodAdditionalData|null
     *
     * @throws InvalidPaymentProductIdException
     * @throws InvalidRecurrenceTypeException
     * @throws InvalidSessionTimeoutException
     * @throws InvalidSignatureTypeException
     */
    protected function additionalDataFromArray(array $data): ?PaymentMethodAdditionalData
    {
        $additionalData = $data['additionalData'] ?? [];

        if (!$additionalData) {
            return null;
        }

        if (PaymentProductId::bankTransfer()->equals($data['paymentProductId'])) {
            return new BankTransfer(
                $additionalData['instantPayment'] ?? false
            );
        }

        if (PaymentProductId::cards()->equals($data['paymentProductId'])) {
            $firstTranslation = $additionalData['vaultTitleCollection'][0] ?? null;

            if (empty($firstTranslation)) {
                return null;
            }

            $vaultTitles = new TranslationCollection(new Translation($firstTranslation['languageCode'], $firstTranslation['title']));
            unset($additionalData['vaultTitleCollection'][0]);

            foreach ($additionalData['vaultTitleCollection'] as $vaultTitle) {
                $vaultTitles->addTranslation(new Translation($vaultTitle['languageCode'], $vaultTitle['title']));
            }

            return new CreditCard($vaultTitles);
        }

        if (PaymentProductId::hostedCheckout()->equals($data['paymentProductId'])) {
            return new HostedCheckout(
                $additionalData['logo'] ?? '',
                $additionalData['enableGroupCards'] ?? false
            );
        }

        if (PaymentProductId::intersolve()->equals($data['paymentProductId'])) {
            return new Intersolve(
                $additionalData['sessionTimeout'] ? new Intersolve\SessionTimeout($additionalData['sessionTimeout']) : null,
                $additionalData['paymentProductId'] ? Intersolve\PaymentProductId::parse($additionalData['paymentProductId']) : null
            );
        }

        if (PaymentProductId::oney3x()->equals($data['paymentProductId']) ||
            PaymentProductId::oney4x()->equals($data['paymentProductId']) ||
            PaymentProductId::oneyFinancementLong()->equals($data['paymentProductId']) ||
            PaymentProductId::oneyBrandedGiftCard()->equals($data['paymentProductId']) ||
            PaymentProductId::oneyBankCard()->equals($data['paymentProductId'])) {
            return new Oney(
                $additionalData['paymentOption'] ?? ''
            );
        }

        if (PaymentProductId::sepaDirectDebit()->equals($data['paymentProductId'])) {
            return new Sepa(
                isset($additionalData['recurrenceType']) ? Sepa\RecurrenceType::parse($additionalData['recurrenceType']) : null,
                isset($additionalData['signatureType']) ? Sepa\SignatureType::parse($additionalData['signatureType']) : null
            );
        }

        return null;
    }

    /**
     * @return array
     */
    protected function additionalDataToArray(): array
    {
        $additionalData = $this->paymentMethod->getAdditionalData() ?? [];

        if (!$additionalData) {
            return [];
        }

        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::bankTransfer()->getId())) {
            return [
                'instantPayment' => $additionalData->isInstantPayment()
            ];
        }

        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::cards()->getId())) {
            $vaultTitles = [];
            foreach ($additionalData->getVaultTitles()->getTranslations() as $vaultTitle) {
                $vaultTitles[] = [
                    'languageCode' => $vaultTitle->getLocaleCode(),
                    'title' => $vaultTitle->getMessage(),
                ];
            }

            return [
                'vaultTitleCollection' => $vaultTitles,
            ];
        }

        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::hostedCheckout()->getId())) {
            return [
                'logo' => $additionalData->getLogo(),
                'enableGroupCards' => $additionalData->isEnableGroupCards(),
            ];
        }

        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::intersolve()->getId())) {
            return [
                'sessionTimeout' => $additionalData->getSessionTimeout()->getDuration(),
                'paymentProductId' => $additionalData->getProductId() ? $additionalData->getProductId()->getId() : null,
            ];
        }

        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::oney3x()->getId()) ||
            $this->paymentMethod->getProductId()->equals(PaymentProductId::oney4x()->getId()) ||
            $this->paymentMethod->getProductId()->equals(PaymentProductId::oneyBankCard()->getId()) ||
            $this->paymentMethod->getProductId()->equals(PaymentProductId::oneyFinancementLong()->getId()) ||
            $this->paymentMethod->getProductId()->equals(PaymentProductId::oneyBrandedGiftCard()->getId())) {
            return [
                'paymentOption' => $additionalData->getPaymentOption(),
            ];
        }

        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::sepaDirectDebit()->getId())) {
            return [
                'recurrenceType' => $additionalData->getRecurrenceType()->getType(),
                'signatureType' => $additionalData->getSignatureType()->getType(),
            ];
        }

        return [];
    }
}