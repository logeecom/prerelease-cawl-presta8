<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\ProductTypes\ProductType;

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
    public function getProductTypesMap(CartProvider $cartProvider): array;
}