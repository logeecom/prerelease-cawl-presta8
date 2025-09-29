<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundResponse;
use CAWL\OnlinePayments\Sdk\Domain\RefundResponse as SdkRefundResponse;
/**
 * CreateRefundResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers
 */
class CreateRefundResponseTransformer
{
    public static function transform(SdkRefundResponse $response) : RefundResponse
    {
        return new RefundResponse(StatusCode::parse((int) $response->getStatusOutput()->getStatusCode()), $response->getStatus());
    }
}
