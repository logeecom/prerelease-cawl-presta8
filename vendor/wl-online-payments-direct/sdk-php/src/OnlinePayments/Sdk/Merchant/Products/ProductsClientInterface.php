<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\Products;

use CAWL\OnlinePayments\Sdk\ApiException;
use CAWL\OnlinePayments\Sdk\AuthorizationException;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\InvalidResponseException;
use CAWL\OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProduct;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProductNetworksResponse;
use CAWL\OnlinePayments\Sdk\Domain\ProductDirectory;
use CAWL\OnlinePayments\Sdk\IdempotenceException;
use CAWL\OnlinePayments\Sdk\PlatformException;
use CAWL\OnlinePayments\Sdk\ReferenceException;
use CAWL\OnlinePayments\Sdk\ValidationException;
/**
 * Products client interface.
 * @internal
 */
interface ProductsClientInterface
{
    /**
     * Resource /v2/{merchantId}/products - Get payment products
     *
     * @param GetPaymentProductsParams $query
     * @param CallContext|null $callContext
     * @return GetPaymentProductsResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getPaymentProducts(GetPaymentProductsParams $query, ?CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/products/{paymentProductId} - Get payment product
     *
     * @param int $paymentProductId
     * @param GetPaymentProductParams $query
     * @param CallContext|null $callContext
     * @return PaymentProduct
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getPaymentProduct($paymentProductId, GetPaymentProductParams $query, ?CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/products/{paymentProductId}/networks - Get payment product networks
     *
     * @param int $paymentProductId
     * @param GetPaymentProductNetworksParams $query
     * @param CallContext|null $callContext
     * @return PaymentProductNetworksResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getPaymentProductNetworks($paymentProductId, GetPaymentProductNetworksParams $query, ?CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/products/{paymentProductId}/directory - Get payment product directory
     *
     * @param int $paymentProductId
     * @param GetProductDirectoryParams $query
     * @param CallContext|null $callContext
     * @return ProductDirectory
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getProductDirectory($paymentProductId, GetProductDirectoryParams $query, ?CallContext $callContext = null);
}
