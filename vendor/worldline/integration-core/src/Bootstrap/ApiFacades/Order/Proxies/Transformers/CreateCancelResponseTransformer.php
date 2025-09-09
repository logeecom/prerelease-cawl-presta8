<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use CAWL\OnlinePayments\Sdk\Domain\CancelPaymentResponse;
/**
 * CreateCancelResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers
 * @internal
 */
class CreateCancelResponseTransformer
{
    public static function transform(CancelPaymentResponse $response) : CancelResponse
    {
        $payment = $response->getPayment();
        return new CancelResponse(StatusCode::parse((int) $payment->getStatusOutput()->getStatusCode()), $payment->getStatus());
    }
}
