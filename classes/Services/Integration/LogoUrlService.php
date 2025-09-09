<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Classes\Services\ImageHandler;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo\LogoUrlService as CoreLogoUrlService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class LogoUrlService
 *
 * @package OnlinePayments\Classes\Services\Integration
 * @internal
 */
class LogoUrlService implements CoreLogoUrlService
{
    /**
     * @inheritDoc
     */
    public function getHostedCheckoutLogoUrl() : string
    {
        $storeId = StoreContext::getInstance()->getStoreId();
        /** @var ActiveConnectionProvider $activeConnectionProvider */
        $activeConnectionProvider = ServiceRegister::getService(ActiveConnectionProvider::class);
        $mode = StoreContext::doWithStore($storeId, function () use($activeConnectionProvider) {
            return (string) $activeConnectionProvider->get()->getMode();
        });
        $url = ImageHandler::getImageUrl(PaymentProductId::HOSTED_CHECKOUT, $storeId, $mode);
        if (!$url) {
            $shop = new \Shop($storeId);
            /** @var \Module $module */
            $module = ServiceRegister::getService(\Module::class);
            $url = \rtrim($shop->getBaseURL(), '/') . $module->getPathUri() . 'views/assets/images/payment_products/hosted_checkout.svg';
        }
        return $url;
    }
    /**
     * @inheritDoc
     */
    public function getLogoUrl(string $productId) : string
    {
        $storeId = StoreContext::getInstance()->getStoreId();
        $shop = new \Shop($storeId);
        /** @var \Module $module */
        $module = ServiceRegister::getService(\Module::class);
        return \rtrim($shop->getBaseURL(), '/') . $module->getPathUri() . 'views/assets/images/payment_products/' . $productId . '.svg';
    }
}
