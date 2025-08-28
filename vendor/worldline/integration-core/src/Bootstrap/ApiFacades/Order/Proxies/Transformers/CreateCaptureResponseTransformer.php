<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers;


use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureResponse;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use OnlinePayments\Sdk\Domain\CaptureResponse as SdkCaptureResponse;

/**
 * CreateCaptureResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers
 */
class CreateCaptureResponseTransformer
{
    public static function transform(SdkCaptureResponse $response): CaptureResponse
    {
        return new CaptureResponse(
            StatusCode::parse((int) $response->getStatusOutput()->getStatusCode()),
            $response->getStatus()
        );
    }
}