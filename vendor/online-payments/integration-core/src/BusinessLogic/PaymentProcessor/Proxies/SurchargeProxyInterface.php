<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeResponse;
/**
 * Interface SurchargeProxyInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies
 */
interface SurchargeProxyInterface
{
    /**
     * @param SurchargeRequest $request
     *
     * @return SurchargeResponse|null
     */
    public function calculateSurcharge(SurchargeRequest $request) : ?SurchargeResponse;
}
