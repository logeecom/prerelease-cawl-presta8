<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedTokenization\ValidTokensResponse;
/**
 * Class PaymentMethodsResponse.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response
 * @internal
 */
class PaymentMethodsResponse extends Response
{
    private PaymentMethodCollection $availablePaymentMethods;
    private ?ValidTokensResponse $validTokensResponse;
    /**
     * @param PaymentMethodCollection $availablePaymentMethods
     * @param ?ValidTokensResponse $validTokensResponse
     */
    public function __construct(PaymentMethodCollection $availablePaymentMethods, ?ValidTokensResponse $validTokensResponse)
    {
        $this->availablePaymentMethods = $availablePaymentMethods;
        $this->validTokensResponse = $validTokensResponse;
    }
    /**
     * @inheritDoc
     */
    public function toArray() : array
    {
        return ['availablePaymentMethods' => \array_map(static function (PaymentMethod $paymentMethod) {
            return ['productId' => (string) $paymentMethod->getProductId(), 'name' => $paymentMethod->getName()->toArray()];
        }, $this->availablePaymentMethods->toArray())];
    }
    /**
     * @return PaymentMethodCollection
     */
    public function getPaymentMethods() : PaymentMethodCollection
    {
        return $this->availablePaymentMethods;
    }
    public function getValidTokensResponse() : ?ValidTokensResponse
    {
        return $this->validTokensResponse;
    }
}
