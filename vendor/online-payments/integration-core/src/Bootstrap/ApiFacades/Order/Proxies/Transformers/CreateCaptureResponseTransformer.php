<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use CAWL\OnlinePayments\Sdk\Domain\CaptureResponse as SdkCaptureResponse;
/**
 * CreateCaptureResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers
 */
class CreateCaptureResponseTransformer
{
    public static function transform(SdkCaptureResponse $response) : CaptureResponse
    {
        return new CaptureResponse(StatusCode::parse((int) $response->getStatusOutput()->getStatusCode()), $response->getStatus());
    }
}
