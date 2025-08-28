<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundResponse;
use OnlinePayments\Sdk\Domain\RefundResponse as SdkRefundResponse;

/**
 * CreateRefundResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers
 */
class CreateRefundResponseTransformer
{
    public static function transform(SdkRefundResponse $response): RefundResponse
    {
        return new RefundResponse(
            StatusCode::parse((int) $response->getStatusOutput()->getStatusCode()),
            $response->getStatus()
        );
    }
}