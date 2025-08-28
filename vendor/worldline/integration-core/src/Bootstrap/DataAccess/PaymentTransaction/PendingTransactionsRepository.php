<?php

namespace OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction;

use DateInterval;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use OnlinePayments\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Class PendingTransactionsRepository.
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction
 */
class PendingTransactionsRepository
{
    private RepositoryInterface $repository;
    private TimeProviderInterface $timeProvider;

    public function __construct(
        RepositoryInterface $repository,
        TimeProviderInterface $timeProvider
    ) {
        $this->repository = $repository;
        $this->timeProvider = $timeProvider;
    }

    /**
     * @param int $limit
     * @return PaymentTransactionEntity[]
     */
    public function get(int $limit = 10): array
    {
        $queryFilter = new QueryFilter();

        $oneDayOld = $this->timeProvider->getCurrentLocalTime()->sub(new DateInterval('P1D'));
        $queryFilter
            ->where('statusCode', Operators::IN, StatusCode::PENDING_STATUS_CODES)
            ->where('createdAtTimestamp', Operators::GREATER_OR_EQUAL_THAN, $oneDayOld->getTimestamp())
            ->orderBy('updatedAtTimestamp', QueryFilter::ORDER_DESC)
            ->setLimit($limit);


        /** @var ?PaymentTransactionEntity[] $entities */
        $entities = $this->repository->select($queryFilter);

        return $entities;
    }
}