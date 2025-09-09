<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use CAWL\OnlinePayments\Sdk\Merchant\Products\GetPaymentProductsParams;
/**
 * Class GetPaymentProductsParamsTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\RequestTransformers
 * @internal
 */
class GetPaymentProductsParamsTransformer
{
    public static function transform(Cart $cart) : GetPaymentProductsParams
    {
        $paymentProductsParams = new GetPaymentProductsParams();
        $paymentProductsParams->setCountryCode($cart->getCustomer()->getBillingAddress()->getCountry()->getIsoCode());
        $paymentProductsParams->setAmount($cart->getTotal()->getValue());
        $paymentProductsParams->setCurrencyCode($cart->getTotal()->getCurrency()->getIsoCode());
        return $paymentProductsParams;
    }
}
