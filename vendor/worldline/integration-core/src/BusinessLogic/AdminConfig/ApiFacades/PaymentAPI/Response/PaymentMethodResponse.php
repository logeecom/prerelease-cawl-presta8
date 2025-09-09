<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
/**
 * Class PaymentMethodResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response
 */
class PaymentMethodResponse extends Response
{
    private PaymentMethod $paymentMethod;
    /**
     * @param PaymentMethod $paymentMethod
     */
    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }
    /**
     * @inheritDoc
     */
    public function toArray() : array
    {
        return ['paymentProductId' => (string) $this->paymentMethod->getProductId(), 'name' => $this->paymentMethod->getName()->toArray(), 'enabled' => $this->paymentMethod->isEnabled(), 'template' => $this->paymentMethod->getTemplate(), 'additionalData' => $this->additionalDataToArray()];
    }
    /**
     * @return array
     */
    protected function additionalDataToArray() : array
    {
        $additionalData = $this->paymentMethod->getAdditionalData() ?? [];
        if (!$additionalData) {
            return [];
        }
        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::bankTransfer()->getId())) {
            return ['instantPayment' => $additionalData->isInstantPayment()];
        }
        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::cards()->getId())) {
            return ['vaultTitleCollection' => $this->paymentMethod->getAdditionalData()->getVaultTitles()->toArray()];
        }
        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::hostedCheckout()->getId())) {
            return ['logo' => $additionalData->getLogo(), 'enableGroupCards' => $additionalData->isEnableGroupCards()];
        }
        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::intersolve()->getId())) {
            return ['sessionTimeout' => $additionalData->getSessionTimeout()->getDuration(), 'paymentProductId' => $additionalData->getProductId() ? $additionalData->getProductId()->getId() : null];
        }
        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::oney3x()->getId()) || $this->paymentMethod->getProductId()->equals(PaymentProductId::oney4x()->getId()) || $this->paymentMethod->getProductId()->equals(PaymentProductId::oneyBankCard()->getId()) || $this->paymentMethod->getProductId()->equals(PaymentProductId::oneyFinancementLong()->getId()) || $this->paymentMethod->getProductId()->equals(PaymentProductId::oneyBrandedGiftCard()->getId())) {
            return ['paymentOption' => $additionalData->getPaymentOption()];
        }
        if ($this->paymentMethod->getProductId()->equals(PaymentProductId::sepaDirectDebit()->getId())) {
            return ['recurrenceType' => $additionalData->getRecurrenceType()->getType(), 'signatureType' => $additionalData->getSignatureType()->getType()];
        }
        return [];
    }
}
