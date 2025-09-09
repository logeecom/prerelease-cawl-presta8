<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\ProductTypes\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ProductTypes\ProductType;
/**
 * Interface ProductTypeRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\ProductTypes\Repositories
 */
interface ProductTypeRepositoryInterface
{
    public function getByProduct(string $productId) : ?ProductType;
    public function assignTypeToProduct(string $productId, ProductType $productType) : void;
    public function removeAssignmentFromProduct(string $productId) : void;
}
