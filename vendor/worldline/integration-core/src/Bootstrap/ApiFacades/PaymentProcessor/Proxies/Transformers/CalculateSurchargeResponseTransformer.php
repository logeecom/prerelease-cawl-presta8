<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Exceptions\InvalidCurrencyCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeResponse;
use CAWL\OnlinePayments\Sdk\Domain\CalculateSurchargeResponse;
/**
 * Class CalculateSurchargeResponseTransformer
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 * @internal
 */
class CalculateSurchargeResponseTransformer
{
    /**
     * @param CalculateSurchargeResponse $response
     *
     * @return SurchargeResponse|null
     *
     * @throws InvalidCurrencyCode
     */
    public static function transform(CalculateSurchargeResponse $response) : ?SurchargeResponse
    {
        $surcharges = $response->getSurcharges();
        if (empty($surcharges)) {
            return null;
        }
        $surcharge = $surcharges[0];
        return new SurchargeResponse(Amount::fromInt($surcharge->getNetAmount()->getAmount(), Currency::fromIsoCode($surcharge->getNetAmount()->getCurrencyCode())), Amount::fromInt($surcharge->getSurchargeAmount()->getAmount(), Currency::fromIsoCode($surcharge->getSurchargeAmount()->getCurrencyCode())), Amount::fromInt($surcharge->getTotalAmount()->getAmount(), Currency::fromIsoCode($surcharge->getTotalAmount()->getCurrencyCode())));
    }
}
