<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationResponse;

/**
 * Class CreateHostedTokenizationResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreateHostedTokenizationResponseTransformer
{

    public static function transform(CreateHostedTokenizationResponse $response): HostedTokenization
    {
        return new HostedTokenization(
            (string)$response->getHostedTokenizationUrl(),
                $response->getInvalidTokens() ?? []
        );
    }
}