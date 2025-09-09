<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Request;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Request\Request;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidPaymentProductIdException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidRecurrenceTypeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidSessionTimeoutException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidSignatureTypeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\BankTransfer;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\CreditCard;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\HostedCheckout;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Intersolve;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Oney;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Sepa;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
/**
 * Class PaymentMethodRequest
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Request
 * @internal
 */
class PaymentMethodRequest extends Request
{
    protected string $productId;
    /**
     * @var array<string, string>
     */
    protected array $name;
    protected bool $enabled;
    protected string $template;
    // credit card additional data
    /**
     * @var array<string, string> | null
     */
    protected ?array $vaultTitles = [];
    // hosted checkout
    protected ?string $logo;
    protected ?bool $enableGroupCards;
    // Oney
    protected ?string $paymentOption;
    // Intersolve
    protected ?int $sessionTimeout;
    protected ?string $intersolveProductId;
    // Sepa
    protected ?string $recurrenceType;
    protected ?string $signatureType;
    // Bank Transfer
    protected ?bool $instantPayment;
    /**
     * @param string $productId
     * @param array $name
     * @param bool $enabled
     * @param string $template
     * @param string[]|null $vaultTitles
     * @param string|null $logo
     * @param bool|null $enableGroupCards
     * @param string|null $paymentOption
     * @param int|null $sessionTimeout
     * @param string|null $intersolveProductId
     * @param string|null $recurrenceType
     * @param string|null $signatureType
     * @param bool|null $instantPayment
     */
    public function __construct(string $productId, array $name, bool $enabled, string $template, ?array $vaultTitles = [], ?string $logo = null, ?bool $enableGroupCards = null, ?string $paymentOption = null, ?int $sessionTimeout = null, ?string $intersolveProductId = null, ?string $recurrenceType = null, ?string $signatureType = null, ?bool $instantPayment = null)
    {
        $this->productId = $productId;
        $this->name = $name;
        $this->enabled = $enabled;
        $this->template = $template;
        $this->vaultTitles = $vaultTitles;
        $this->logo = $logo;
        $this->enableGroupCards = $enableGroupCards;
        $this->paymentOption = $paymentOption;
        $this->sessionTimeout = $sessionTimeout;
        $this->intersolveProductId = $intersolveProductId;
        $this->recurrenceType = $recurrenceType;
        $this->signatureType = $signatureType;
        $this->instantPayment = $instantPayment;
    }
    /**
     * @inheritDoc
     * @throws InvalidRecurrenceTypeException
     * @throws InvalidSignatureTypeException
     * @throws InvalidSessionTimeoutException
     * @throws InvalidPaymentProductIdException
     */
    public function transformToDomainModel() : object
    {
        $additionalData = null;
        if (PaymentProductId::bankTransfer()->equals($this->productId)) {
            $additionalData = new BankTransfer($this->instantPayment ?? \false);
        }
        if (PaymentProductId::cards()->equals($this->productId)) {
            $defaultLanguage = \array_key_first($this->vaultTitles);
            $defaultTranslation = $this->vaultTitles[$defaultLanguage];
            $vaultTitles = new TranslationCollection(new Translation($defaultLanguage, $defaultTranslation));
            unset($this->vaultTitles[$defaultLanguage]);
            foreach ($this->vaultTitles as $language => $vaultTitle) {
                $vaultTitles->addTranslation(new Translation($language, $vaultTitle));
            }
            $additionalData = new CreditCard($vaultTitles);
        }
        if (PaymentProductId::hostedCheckout()->equals($this->productId)) {
            $additionalData = new HostedCheckout($this->logo ?? '', $this->enableGroupCards ?? \true);
        }
        if (PaymentProductId::intersolve()->equals($this->productId)) {
            $additionalData = new Intersolve(new Intersolve\SessionTimeout($this->sessionTimeout ?: 180), Intersolve\PaymentProductId::parse($this->intersolveProductId ?: '5700'));
        }
        if (PaymentProductId::oney3x()->equals($this->productId) || PaymentProductId::oney4x()->equals($this->productId) || PaymentProductId::oneyFinancementLong()->equals($this->productId) || PaymentProductId::oneyBrandedGiftCard()->equals($this->productId) || PaymentProductId::oneyBankCard()->equals($this->productId)) {
            $additionalData = new Oney($this->paymentOption ?? '');
        }
        if (PaymentProductId::sepaDirectDebit()->equals($this->productId)) {
            $additionalData = new Sepa($this->recurrenceType ? Sepa\RecurrenceType::parse($this->recurrenceType) : Sepa\RecurrenceType::unique(), isset($this->signatureType) ? Sepa\SignatureType::parse($this->signatureType) : Sepa\SignatureType::sms());
        }
        $firstLanguage = \array_key_first($this->name);
        $firstName = $this->name[$firstLanguage];
        $nameCollection = new TranslationCollection(new Translation($firstLanguage, $firstName));
        unset($this->name[$firstLanguage]);
        foreach ($this->name as $language => $name) {
            $nameCollection->addTranslation(new Translation($language, $name));
        }
        return new PaymentMethod(PaymentProductId::parse($this->productId), $nameCollection, $this->enabled, $this->template, $additionalData);
    }
}
