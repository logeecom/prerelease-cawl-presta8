<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Response;

use OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use OnlinePayments\Core\BusinessLogic\Domain\Stores\Models\StoreOrderStatus;

/**
 * Class StoreOrderStatusesResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Response
 */
class StoreOrderStatusesResponse extends Response
{
    /**
     * @var StoreOrderStatus[]
     */
    private array $storeOrderStatuses;

    /**
     * @param StoreOrderStatus[] $storeOrderStatuses
     */
    public function __construct(array $storeOrderStatuses)
    {
        $this->storeOrderStatuses = $storeOrderStatuses;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->storeOrderStatuses as $storeOrderStatus) {
            $result[] = [
                'value' => $storeOrderStatus->getStatusId(),
                'label' => $storeOrderStatus->getStatusName(),
            ];
        }

        return $result;
    }
}
