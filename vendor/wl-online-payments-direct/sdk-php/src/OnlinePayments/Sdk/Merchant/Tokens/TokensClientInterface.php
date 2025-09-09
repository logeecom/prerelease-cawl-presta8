<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\Tokens;

use CAWL\OnlinePayments\Sdk\ApiException;
use CAWL\OnlinePayments\Sdk\AuthorizationException;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\InvalidResponseException;
use CAWL\OnlinePayments\Sdk\Domain\CreateTokenRequest;
use CAWL\OnlinePayments\Sdk\Domain\CreatedTokenResponse;
use CAWL\OnlinePayments\Sdk\Domain\TokenResponse;
use CAWL\OnlinePayments\Sdk\IdempotenceException;
use CAWL\OnlinePayments\Sdk\PlatformException;
use CAWL\OnlinePayments\Sdk\ReferenceException;
use CAWL\OnlinePayments\Sdk\ValidationException;
/**
 * Tokens client interface.
 * @internal
 */
interface TokensClientInterface
{
    /**
     * Resource /v2/{merchantId}/tokens/{tokenId} - Get token
     *
     * @param string $tokenId
     * @param CallContext|null $callContext
     * @return TokenResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getToken($tokenId, ?CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/tokens/{tokenId} - Delete token
     *
     * @param string $tokenId
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
    function deleteToken($tokenId, ?CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/tokens - Create token
     *
     * @param CreateTokenRequest $body
     * @param CallContext|null $callContext
     * @return CreatedTokenResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function createToken(CreateTokenRequest $body, ?CallContext $callContext = null);
}
