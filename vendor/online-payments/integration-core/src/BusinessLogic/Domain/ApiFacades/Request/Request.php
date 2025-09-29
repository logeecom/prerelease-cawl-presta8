<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Request;

/**
 * Class Request
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Request
 */
abstract class Request
{
    /**
     * Transform to Domain model based on data sent from controller.
     *
     * @return object
     */
    public abstract function transformToDomainModel() : object;
}
