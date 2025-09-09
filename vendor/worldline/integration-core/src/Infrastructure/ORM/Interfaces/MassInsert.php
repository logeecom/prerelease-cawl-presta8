<?php

namespace CAWL\OnlinePayments\Core\Infrastructure\ORM\Interfaces;

use CAWL\OnlinePayments\Core\Infrastructure\ORM\Entity;
/**
 * Interface MassInsert.
 *
 * @package OnlinePayments\Core\Infrastructure\ORM\Interfaces
 * @internal
 */
interface MassInsert extends RepositoryInterface
{
    /**
     * Executes mass insert query for all provided entities
     *
     * @param Entity[] $entities
     *
     * @return void
     */
    public function massInsert(array $entities) : void;
}
