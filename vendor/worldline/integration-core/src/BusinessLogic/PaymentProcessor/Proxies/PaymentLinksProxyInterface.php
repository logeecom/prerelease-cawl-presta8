<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkResponse;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;

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
        PayByLinkSettings $payByLinkSettings,
        PaymentMethodCollection $paymentMethodCollection
    ): PaymentLinkResponse;

    public function getById(string $paymentLinkId, string $merchantReference): PaymentLinkResponse;
}