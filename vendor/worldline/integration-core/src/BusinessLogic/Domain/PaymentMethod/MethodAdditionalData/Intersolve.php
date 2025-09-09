<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Intersolve\PaymentProductId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Intersolve\SessionTimeout;
/**
 * Class Intersolve
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\RedirectPaymentMethod
 * @internal
 */
class Intersolve implements PaymentMethodAdditionalData
{
    protected SessionTimeout $sessionTimeout;
    protected ?PaymentProductId $productId = null;
    /**
     * @param SessionTimeout $sessionTimeout
     * @param PaymentProductId|null $productId
     */
    public function __construct(SessionTimeout $sessionTimeout, ?PaymentProductId $productId)
    {
        $this->sessionTimeout = $sessionTimeout;
        $this->productId = $productId;
    }
    /**
     * @return SessionTimeout
     */
    public function getSessionTimeout() : SessionTimeout
    {
        return $this->sessionTimeout;
    }
    /**
     * @return PaymentProductId|null
     */
    public function getProductId() : ?PaymentProductId
    {
        return $this->productId;
    }
}
