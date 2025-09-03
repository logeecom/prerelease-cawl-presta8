<?php

namespace OnlinePayments\Classes\Utility;

use Context;
use OnlinePayments\Classes\OnlinePaymentsModule;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use PrestaShopException;

/**
 * Class Url
 *
 * @package OnlinePayments\Classes\Utility
 */
class Url
{
    /**
     * Gets the URL of the admin controller and its action.
     *
     * @param string $controller
     * @param string $action
     * @param string|null $storeId
     * @param string|null $methodId
     * @param string|null $queueItemId
     * @param bool $ajax
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getAdminUrl(
        string $controller,
        string $action = null,
        string $storeId = null,
        string $methodId = null,
        string $queueItemId = null,
        bool $ajax = true
    ): string {
        $url = Context::getContext()->link->getAdminLink($controller) . '&';
        $params = [
            'ajax' => $ajax,
            'action' => $action
        ];

        $queryString = http_build_query($params);

        self::addQueryParam($queryString, 'storeId', $storeId);
        self::addQueryParam($queryString, 'methodId', $methodId);
        self::addQueryParam($queryString, 'queueItemId', $queueItemId);

        return $url . $queryString;
    }

    /**
     * Gets the URL of the frontend controller.
     *
     * @param string $controller
     * @param array $params
     *
     * @return string
     */
    public static function getFrontUrl(string $controller, array $params = []): string
    {
        /** @var OnlinePaymentsModule $module */
        $module = ServiceRegister::getService(\Module::class);

        $shopId = StoreContext::getInstance()->getStoreId();

        return Context::getContext()->link->getModuleLink(
            $module->name,
            $controller,
            $params,
            null,
            null,
            $shopId ?: Context::getContext()->shop->id
        );
    }

    /**
     * Gets the URL of the admin controller without query params.
     *
     * @param string $controller
     *
     * @return string
     */
    public static function getAdminController(string $controller): string
    {
        return Context::getContext()->link->getAdminLink($controller);
    }

    /**
     * Adds query parameter if its value is different from null.
     *
     * @param string $queryString
     * @param string $queryParamName
     * @param string|null $queryParamValue
     *
     * @return void
     */
    private static function addQueryParam(string &$queryString, string $queryParamName, ?string $queryParamValue): void
    {
        if ($queryParamValue !== null) {
            $queryString .= '&' . $queryParamName . '=' . $queryParamValue;
        }
    }
}