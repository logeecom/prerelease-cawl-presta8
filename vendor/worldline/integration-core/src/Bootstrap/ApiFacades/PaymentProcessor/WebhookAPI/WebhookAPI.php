<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\WebhookAPI;

use OnlinePayments\Core\Bootstrap\ApiFacades\Aspects\ErrorHandlingAspect;
use OnlinePayments\Core\Bootstrap\ApiFacades\Aspects\StoreContextAspect;
use OnlinePayments\Core\Bootstrap\Aspect\Aspects;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\WebhooksAPI\Controller\WebhooksController;

/**
 * Class WebhookAPI
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\WebhookAPI
 */
class WebhookAPI
{
    private function __construct()
    {
    }

    public static function get(): object
    {
        StoreContext::getInstance()->setOrigin('hooks');

        return Aspects::run(new ErrorHandlingAspect())->beforeEachMethodOfInstance(new WebhookAPI());
    }

    public function webhooks(string $storeId): object
    {
        return Aspects
            ::run(new ErrorHandlingAspect())
            ->andRun(new StoreContextAspect($storeId))
            ->beforeEachMethodOfService(WebhooksController::class);
    }
}
