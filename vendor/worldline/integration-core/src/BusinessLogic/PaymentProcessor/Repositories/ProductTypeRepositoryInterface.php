<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ProductTypes\ProductType;
/**
 * Interface ProductTypeRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories
 */
interface ProductTypeRepositoryInterface
{
    /**
     * Gets a map of cart product ids to the assigned product type
     *
     * @param CartProvider $cartProvider
     * @return array<string, ProductType>
     */
    public function getProductTypesMap(CartProvider $cartProvider) : array;
}
