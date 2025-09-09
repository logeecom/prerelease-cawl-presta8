<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment;

/**
 * Class PaymentId.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Payment
 * @internal
 */
class PaymentId
{
    private string $id;
    private function __construct(string $id)
    {
        $this->id = $id;
    }
    public static function parse(string $id) : PaymentId
    {
        return new self(\false === \strpos($id, '_') ? $id . '_0' : $id);
    }
    /**
     * String representation of the payment method id
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->id;
    }
    /**
     * Returns transaction id without trailing operations sequence indexes.
     * Example "4365991440" will be returned for "4365991440_0" payment id.
     *
     * @return string
     */
    public function getTransactionId() : string
    {
        return (string) \strstr((string) $this, '_', \true);
    }
}
