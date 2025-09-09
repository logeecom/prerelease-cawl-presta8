<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response;

/**
 * Class Response
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response
 * @internal
 */
abstract class Response
{
    /**
     * @var bool
     */
    protected bool $successful = \true;
    /**
     * @var int
     */
    protected int $statusCode = 200;
    /**
     * @return bool
     */
    public function isSuccessful() : bool
    {
        return $this->successful;
    }
    /**
     * @return int
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }
    /**
     * @return array
     */
    public abstract function toArray() : array;
}
