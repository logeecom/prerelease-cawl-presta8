<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentLink;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLink;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories\PaymentLinkRepositoryInterface;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
/**
 * Class PaymentLinkRepository
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\PaymentLink
 * @internal
 */
class PaymentLinkRepository implements PaymentLinkRepositoryInterface
{
    private RepositoryInterface $repository;
    private StoreContext $storeContext;
    public function __construct(RepositoryInterface $repository, StoreContext $storeContext)
    {
        $this->repository = $repository;
        $this->storeContext = $storeContext;
    }
    public function save(PaymentLink $paymentLink) : void
    {
        $existingLink = $this->getPaymentTransactionEntity($paymentLink->getMerchantReference());
        if ($existingLink) {
            $existingLink->setPaymentLink($paymentLink);
            $this->repository->update($existingLink);
            return;
        }
        $entity = new PaymentLinkEntity();
        $entity->setStoreId($this->storeContext->getStoreId());
        $entity->setPaymentLink($paymentLink);
        $this->repository->save($entity);
    }
    public function getByMerchantReference(string $reference) : ?PaymentLink
    {
        $entity = $this->getPaymentTransactionEntity($reference);
        return $entity ? $entity->getPaymentLink() : null;
    }
    private function getPaymentTransactionEntity(string $merchantReference) : ?PaymentLinkEntity
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('merchantReference', Operators::EQUALS, $merchantReference);
        /** @var PaymentLinkEntity|null $entity */
        $entity = $this->repository->selectOne($queryFilter);
        return $entity;
    }
}
