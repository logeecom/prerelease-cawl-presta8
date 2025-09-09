<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant;

use CAWL\OnlinePayments\Sdk\Merchant\Captures\CapturesClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Complete\CompleteClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\HostedCheckout\HostedCheckoutClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\HostedTokenization\HostedTokenizationClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Mandates\MandatesClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\PaymentLinks\PaymentLinksClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Payments\PaymentsClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Payouts\PayoutsClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\PrivacyPolicy\PrivacyPolicyClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\ProductGroups\ProductGroupsClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Products\ProductsClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Refunds\RefundsClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Services\ServicesClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Sessions\SessionsClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Tokens\TokensClientInterface;
use CAWL\OnlinePayments\Sdk\Merchant\Webhooks\WebhooksClientInterface;
/**
 * Merchant client interface.
 * @internal
 */
interface MerchantClientInterface
{
    /**
     * Resource /v2/{merchantId}/hostedcheckouts
     *
     * @return HostedCheckoutClientInterface
     */
    function hostedCheckout();
    /**
     * Resource /v2/{merchantId}/hostedtokenizations
     *
     * @return HostedTokenizationClientInterface
     */
    function hostedTokenization();
    /**
     * Resource /v2/{merchantId}/payments
     *
     * @return PaymentsClientInterface
     */
    function payments();
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/captures
     *
     * @return CapturesClientInterface
     */
    function captures();
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/refunds
     *
     * @return RefundsClientInterface
     */
    function refunds();
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/complete
     *
     * @return CompleteClientInterface
     */
    function complete();
    /**
     * Resource /v2/{merchantId}/productgroups
     *
     * @return ProductGroupsClientInterface
     */
    function productGroups();
    /**
     * Resource /v2/{merchantId}/products
     *
     * @return ProductsClientInterface
     */
    function products();
    /**
     * Resource /v2/{merchantId}/services/testconnection
     *
     * @return ServicesClientInterface
     */
    function services();
    /**
     * Resource /v2/{merchantId}/webhooks/validateCredentials
     *
     * @return WebhooksClientInterface
     */
    function webhooks();
    /**
     * Resource /v2/{merchantId}/sessions
     *
     * @return SessionsClientInterface
     */
    function sessions();
    /**
     * Resource /v2/{merchantId}/tokens/{tokenId}
     *
     * @return TokensClientInterface
     */
    function tokens();
    /**
     * Resource /v2/{merchantId}/payouts/{payoutId}
     *
     * @return PayoutsClientInterface
     */
    function payouts();
    /**
     * Resource /v2/{merchantId}/mandates
     *
     * @return MandatesClientInterface
     */
    function mandates();
    /**
     * Resource /v2/{merchantId}/services/privacypolicy
     *
     * @return PrivacyPolicyClientInterface
     */
    function privacyPolicy();
    /**
     * Resource /v2/{merchantId}/paymentlinks
     *
     * @return PaymentLinksClientInterface
     */
    function paymentLinks();
}
