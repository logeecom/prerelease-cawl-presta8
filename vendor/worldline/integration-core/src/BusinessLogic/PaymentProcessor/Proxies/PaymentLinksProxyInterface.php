<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkResponse;

/**
 * Interface PaymentLinksProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies
 */
interface PaymentLinksProxyInterface
{
    public function create(
        PaymentLinkRequest $request,
        CardsSettings $cardsSettings,
        PaymentSettings $paymentSettings,
        PayByLinkSettings $payByLinkSettings
    ): PaymentLinkResponse;

    public function getById(string $paymentLinkId, string $merchantReference): PaymentLinkResponse;
}