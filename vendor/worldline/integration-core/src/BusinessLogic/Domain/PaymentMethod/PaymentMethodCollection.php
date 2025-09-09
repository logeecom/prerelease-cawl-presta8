<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
/**
 * Class PaymentMethodCollection.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod
 * @internal
 */
class PaymentMethodCollection
{
    /**
     * @var array<string, PaymentMethod>
     */
    private array $paymentMethods = [];
    /**
     * @param PaymentMethod[] $paymentMethods
     */
    public function __construct(array $paymentMethods = [])
    {
        foreach ($paymentMethods as $paymentMethod) {
            $this->add($paymentMethod);
        }
    }
    public function add(PaymentMethod $paymentMethod) : void
    {
        $this->paymentMethods[(string) $paymentMethod->getProductId()] = $paymentMethod;
    }
    public function remove(PaymentProductId $id) : void
    {
        unset($this->paymentMethods[(string) $id]);
    }
    public function get(PaymentProductId $id) : ?PaymentMethod
    {
        return $this->has($id) ? $this->paymentMethods[(string) $id] : null;
    }
    public function has(PaymentProductId $id) : bool
    {
        return \array_key_exists((string) $id, $this->paymentMethods);
    }
    public function intersect(PaymentMethodCollection $other) : PaymentMethodCollection
    {
        $result = new PaymentMethodCollection();
        foreach ($this->paymentMethods as $paymentMethod) {
            if ($other->has($paymentMethod->getProductId())) {
                $result->add($paymentMethod);
            }
        }
        return $result;
    }
    public function union(PaymentMethodCollection $other) : PaymentMethodCollection
    {
        $result = new PaymentMethodCollection($this->paymentMethods);
        foreach ($other->toArray() as $paymentMethod) {
            if (!$this->has($paymentMethod->getProductId())) {
                $result->add($paymentMethod);
            }
        }
        return $result;
    }
    public function isEmpty() : bool
    {
        return empty($this->paymentMethods);
    }
    /**
     * @return PaymentMethod[]
     */
    public function toArray() : array
    {
        return $this->paymentMethods;
    }
}
