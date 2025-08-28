<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData;

/**
 * Class HostedCheckout
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData
 */
class HostedCheckout implements PaymentMethodAdditionalData
{
    protected string $logo;
    protected bool $enableGroupCards;

    /**
     * @param string $logo
     * @param bool $enableGroupCards
     */
    public function __construct(string $logo, bool $enableGroupCards = true)
    {
        $this->logo = $logo;
        $this->enableGroupCards = $enableGroupCards;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function isEnableGroupCards(): bool
    {
        return $this->enableGroupCards;
    }
}
