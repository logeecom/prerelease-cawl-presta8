<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\WebhooksAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
/**
 * Class WebhookResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\WebhooksAPI\Response
 */
class WebhookResponse extends Response
{
    /**
     * @inheritDoc
     */
    public function toArray() : array
    {
        return [];
    }
}
