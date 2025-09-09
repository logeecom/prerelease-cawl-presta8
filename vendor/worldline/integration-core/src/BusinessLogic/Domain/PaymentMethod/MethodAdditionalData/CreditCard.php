<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
/**
 * Class CreditCard
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData
 */
class CreditCard implements PaymentMethodAdditionalData
{
    protected ?TranslationCollection $vaultTitles;
    /**
     * @param TranslationCollection|null $vaultTitles
     */
    public function __construct(?TranslationCollection $vaultTitles = null)
    {
        $this->vaultTitles = $vaultTitles;
    }
    /**
     * @return TranslationCollection|null
     */
    public function getVaultTitles() : ?TranslationCollection
    {
        return $this->vaultTitles;
    }
}
