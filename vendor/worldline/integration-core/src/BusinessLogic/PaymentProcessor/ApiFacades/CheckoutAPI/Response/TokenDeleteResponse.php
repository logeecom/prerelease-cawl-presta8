<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
/**
 * Class TokenDeleteResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response
 * @internal
 */
class TokenDeleteResponse extends Response
{
    /**
     * @inheritDoc
     */
    public function toArray() : array
    {
        return [];
    }
}
