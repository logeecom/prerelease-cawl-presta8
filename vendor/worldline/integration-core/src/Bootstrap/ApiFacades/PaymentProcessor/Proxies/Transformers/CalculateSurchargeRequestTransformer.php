<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeRequest;
use CAWL\OnlinePayments\Sdk\Domain\AmountOfMoney;
use CAWL\OnlinePayments\Sdk\Domain\CalculateSurchargeRequest;
use CAWL\OnlinePayments\Sdk\Domain\CardSource;
/**
 * Class CalculateSurchargeRequestTransformer
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CalculateSurchargeRequestTransformer
{
    /**
     * @param SurchargeRequest $request
     *
     * @return CalculateSurchargeRequest
     */
    public static function transform(SurchargeRequest $request) : CalculateSurchargeRequest
    {
        $calculateSurchargeRequest = new CalculateSurchargeRequest();
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($request->getAmount()->getValue());
        $amountOfMoney->setCurrencyCode($request->getAmount()->getCurrency()->getIsoCode());
        $card = new CardSource();
        $card->setToken($request->getToken());
        $calculateSurchargeRequest->setAmountOfMoney($amountOfMoney);
        $calculateSurchargeRequest->setCardSource($card);
        return $calculateSurchargeRequest;
    }
}
