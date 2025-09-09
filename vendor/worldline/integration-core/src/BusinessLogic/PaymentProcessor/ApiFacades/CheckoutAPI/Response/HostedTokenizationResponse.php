<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
/**
 * Class HostedTokenizationResponse.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response
 * @internal
 */
class HostedTokenizationResponse extends Response
{
    private HostedTokenization $hostedTokenization;
    public function __construct(HostedTokenization $hostedTokenization)
    {
        $this->hostedTokenization = $hostedTokenization;
    }
    public function toArray() : array
    {
        return [];
    }
    public function getHostedTokenization() : HostedTokenization
    {
        return $this->hostedTokenization;
    }
}
