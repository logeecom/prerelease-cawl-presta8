<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\Webhooks;

use CAWL\OnlinePayments\Sdk\ApiException;
use CAWL\OnlinePayments\Sdk\AuthorizationException;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\InvalidResponseException;
use CAWL\OnlinePayments\Sdk\Domain\SendTestRequest;
use CAWL\OnlinePayments\Sdk\Domain\ValidateCredentialsRequest;
use CAWL\OnlinePayments\Sdk\Domain\ValidateCredentialsResponse;
use CAWL\OnlinePayments\Sdk\IdempotenceException;
use CAWL\OnlinePayments\Sdk\PlatformException;
use CAWL\OnlinePayments\Sdk\ReferenceException;
use CAWL\OnlinePayments\Sdk\ValidationException;
/**
 * Webhooks client interface.
 * @internal
 */
interface WebhooksClientInterface
{
    /**
     * Resource /v2/{merchantId}/webhooks/validateCredentials - Validate credentials
     *
     * @param ValidateCredentialsRequest $body
     * @param CallContext|null $callContext
     * @return ValidateCredentialsResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function validateWebhookCredentials(ValidateCredentialsRequest $body, ?CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/webhooks/sendtest - Send test
     *
     * @param SendTestRequest $body
     * @param CallContext|null $callContext
     * @return null
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function sendTestWebhook(SendTestRequest $body, ?CallContext $callContext = null);
}
