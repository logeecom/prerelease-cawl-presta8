<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;

/**
 * Class LineItemCollection.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart
 */
class LineItemCollection
{
    /**
     * @var LineItem[]
     */
    private array $lineItems;

    /**
     * @param LineItem[] $lineItems
     */
    public function __construct(array $lineItems = [])
    {
        $this->lineItems = $lineItems;
    }

    public function add(LineItem $lineItem): void
    {
        $this->lineItems[] = $lineItem;
    }

    public function getTotal(): ?Amount
    {
        if (empty($this->lineItems)) {
            return null;
        }

        return array_reduce($this->lineItems, function (?Amount $total, LineItem $lineItem) {
            if (null === $total) {
                return $lineItem->getTotal();
            }

            return $total->plus($lineItem->getTotal());
        });
    }

    public function getCount(): int
    {
        return count($this->lineItems);
    }

    public function isEmpty(): bool
    {
        return empty($this->lineItems);
    }

    public function toArray(): array
    {
        return $this->lineItems;
    }
}