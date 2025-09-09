<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodResponse;
/**
 * Class PaymentMethodsResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response
 * @internal
 */
class PaymentMethodsResponse extends Response
{
    /**
     * @var PaymentMethodResponse[]
     */
    private array $paymentMethods;
    /**
     * @param array $paymentMethods
     */
    public function __construct(array $paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
    }
    /**
     * @inheritDoc
     */
    public function toArray() : array
    {
        $result = [];
        foreach ($this->paymentMethods as $paymentMethod) {
            $result[] = ['paymentProductId' => $paymentMethod->getPaymentProductId(), 'name' => $paymentMethod->getName()->getDefaultTranslation()->toArray(), 'paymentGroup' => $paymentMethod->getPaymentGroup(), 'integrationTypes' => $paymentMethod->getIntegrationTypes(), 'enabled' => $paymentMethod->isEnabled()];
        }
        return $result;
    }
}
