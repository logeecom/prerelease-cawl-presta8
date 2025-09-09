<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use CAWL\OnlinePayments\Sdk\Domain\AmountOfMoney;
use CAWL\OnlinePayments\Sdk\Domain\CancelPaymentRequest;
/**
 * CreateCancelRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers
 */
class CreateCancelRequestTransformer
{
    public static function transform(CancelRequest $captureRequest) : CancelPaymentRequest
    {
        $sdkRequest = new CancelPaymentRequest();
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($captureRequest->getAmount()->getValue());
        $amountOfMoney->setCurrencyCode($captureRequest->getAmount()->getCurrency()->getIsoCode());
        $sdkRequest->setAmountOfMoney($amountOfMoney);
        return $sdkRequest;
    }
}
