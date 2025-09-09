<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\Refunds;

use CAWL\OnlinePayments\Sdk\ApiException;
use CAWL\OnlinePayments\Sdk\AuthorizationException;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\InvalidResponseException;
use CAWL\OnlinePayments\Sdk\Domain\RefundsResponse;
use CAWL\OnlinePayments\Sdk\IdempotenceException;
use CAWL\OnlinePayments\Sdk\PlatformException;
use CAWL\OnlinePayments\Sdk\ReferenceException;
use CAWL\OnlinePayments\Sdk\ValidationException;
/**
 * Refunds client interface.
 * @internal
 */
interface RefundsClientInterface
{
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/refunds - Get refunds of payment
     *
     * @param string $paymentId
     * @param CallContext|null $callContext
     * @return RefundsResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getRefunds($paymentId, ?CallContext $callContext = null);
}
