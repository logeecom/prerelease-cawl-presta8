<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\WebhookAPI;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Aspects\ErrorHandlingAspect;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Aspects\StoreContextAspect;
use CAWL\OnlinePayments\Core\Bootstrap\Aspect\Aspects;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\WebhooksAPI\Controller\WebhooksController;
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
    public static function get() : object
    {
        StoreContext::getInstance()->setOrigin('hooks');
        return Aspects::run(new ErrorHandlingAspect())->beforeEachMethodOfInstance(new WebhookAPI());
    }
    public function webhooks(string $storeId) : object
    {
        return Aspects::run(new ErrorHandlingAspect())->andRun(new StoreContextAspect($storeId))->beforeEachMethodOfService(WebhooksController::class);
    }
}
