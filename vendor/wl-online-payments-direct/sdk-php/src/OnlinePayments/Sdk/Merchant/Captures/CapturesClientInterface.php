<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\Captures;

use CAWL\OnlinePayments\Sdk\ApiException;
use CAWL\OnlinePayments\Sdk\AuthorizationException;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\InvalidResponseException;
use CAWL\OnlinePayments\Sdk\Domain\CapturesResponse;
use CAWL\OnlinePayments\Sdk\IdempotenceException;
use CAWL\OnlinePayments\Sdk\PlatformException;
use CAWL\OnlinePayments\Sdk\ReferenceException;
use CAWL\OnlinePayments\Sdk\ValidationException;
/**
 * Captures client interface.
 * @internal
 */
interface CapturesClientInterface
{
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/captures - Get captures of payment
     *
     * @param string $paymentId
     * @param CallContext|null $callContext
     * @return CapturesResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getCaptures($paymentId, ?CallContext $callContext = null);
}
