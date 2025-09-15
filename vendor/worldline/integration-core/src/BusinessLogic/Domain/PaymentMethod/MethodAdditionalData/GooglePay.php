<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\ThreeDSSettings\ThreeDSSettings;
/**
 * Class GooglePay
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData
 */
class GooglePay implements PaymentMethodAdditionalData
{
    protected ThreeDSSettings $threeDSSettings;
    /**
     * @param ThreeDSSettings $threeDSSettings
     */
    public function __construct(ThreeDSSettings $threeDSSettings)
    {
        $this->threeDSSettings = $threeDSSettings;
    }
    public function getThreeDSSettings() : ThreeDSSettings
    {
        return $this->threeDSSettings;
    }
}
