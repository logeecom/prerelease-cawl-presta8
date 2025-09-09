<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Aspects\ErrorHandlingAspect;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Aspects\StoreContextAspect;
use CAWL\OnlinePayments\Core\Bootstrap\Aspect\Aspects;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CancelAPI\Controller\CancelController;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CaptureAPI\Controller\CaptureController;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\ApiFacades\OrdersAPI\Controller\OrderController;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\ApiFacades\RefundAPI\Controller\RefundController;
/**
 * Class OrderAPI. Integrations should use this class for communicating with Order API.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI
 * @internal
 */
class OrderAPI
{
    private function __construct()
    {
    }
    /**
     * @return OrderAPI
     */
    public static function get() : object
    {
        StoreContext::getInstance()->setOrigin('order');
        return Aspects::run(new ErrorHandlingAspect())->beforeEachMethodOfInstance(new OrderAPI());
    }
    public function orders(string $storeId) : object
    {
        return Aspects::run(new ErrorHandlingAspect())->andRun(new StoreContextAspect($storeId))->beforeEachMethodOfService(OrderController::class);
    }
    public function capture(string $storeId) : object
    {
        return Aspects::run(new ErrorHandlingAspect())->andRun(new StoreContextAspect($storeId))->beforeEachMethodOfService(CaptureController::class);
    }
    public function cancel(string $storeId) : object
    {
        return Aspects::run(new ErrorHandlingAspect())->andRun(new StoreContextAspect($storeId))->beforeEachMethodOfService(CancelController::class);
    }
    public function refund(string $storeId) : object
    {
        return Aspects::run(new ErrorHandlingAspect())->andRun(new StoreContextAspect($storeId))->beforeEachMethodOfService(RefundController::class);
    }
}
