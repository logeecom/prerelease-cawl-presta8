<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Aspects;

use Exception;
use CAWL\OnlinePayments\Core\Bootstrap\Aspect\Aspect;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
/**
 * Class StoreContextAspect.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Aspects
 * @internal
 */
class StoreContextAspect implements Aspect
{
    /**
     * @var string
     */
    private string $storeId;
    /**
     * @param string $storeId
     */
    public function __construct(string $storeId)
    {
        $this->storeId = $storeId;
    }
    /**
     * @throws Exception
     */
    public function applyOn($callee, array $params = [])
    {
        return StoreContext::doWithStore($this->storeId, $callee, $params);
    }
}
