<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\Aspect;

/**
 * Interface Aspect
 *
 * @package OnlinePayments\Core\Bootstrap\Aspect
 * @internal
 */
interface Aspect
{
    public function applyOn($callee, array $params = []);
}
