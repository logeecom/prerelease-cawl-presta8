<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
/**
 * Class PaymentTransactionRepository.
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction
 */
class PaymentTransactionRepository implements PaymentTransactionRepositoryInterface
{
    private RepositoryInterface $repository;
    private StoreContext $storeContext;
    private TimeProviderInterface $timeProvider;
    public function __construct(RepositoryInterface $repository, StoreContext $storeContext, TimeProviderInterface $timeProvider)
    {
        $this->repository = $repository;
        $this->storeContext = $storeContext;
        $this->timeProvider = $timeProvider;
    }
    public function save(PaymentTransaction $paymentTransaction) : void
    {
        $existingTransaction = $this->getPaymentTransactionEntity($paymentTransaction->getPaymentId(), null, $paymentTransaction->getPaymentLinkId());
        $paymentTransaction->setUpdatedAt($this->timeProvider->getCurrentLocalTime());
        if ($existingTransaction) {
            $existingTransaction->setPaymentTransaction($paymentTransaction);
            $this->repository->update($existingTransaction);
            return;
        }
        $paymentTransaction->setCreatedAt($this->timeProvider->getCurrentLocalTime());
        $entity = new PaymentTransactionEntity();
        $entity->setStoreId($this->storeContext->getStoreId());
        $entity->setPaymentTransaction($paymentTransaction);
        $this->repository->save($entity);
    }
    public function updatePaymentId(PaymentTransaction $paymentTransaction, PaymentId $paymentId) : void
    {
        $existingTransaction = $this->getPaymentTransactionEntity(null, null, $paymentTransaction->getPaymentLinkId());
        if (!$existingTransaction) {
            return;
        }
        $paymentTransaction->setUpdatedAt($this->timeProvider->getCurrentLocalTime());
        $paymentTransaction->setPaymentId($paymentId);
        $existingTransaction->setPaymentTransaction($paymentTransaction);
        $this->repository->update($existingTransaction);
    }
    public function get(PaymentId $paymentId, ?string $returnHmac = null) : ?PaymentTransaction
    {
        $entity = $this->getPaymentTransactionEntity($paymentId, $returnHmac);
        return $entity ? $entity->getPaymentTransaction() : null;
    }
    public function getByPaymentLinkId(string $paymentLinkId) : ?PaymentTransaction
    {
        $entity = $this->getPaymentTransactionEntity(null, null, $paymentLinkId);
        return $entity ? $entity->getPaymentTransaction() : null;
    }
    /**
     * @param string $reference
     *
     * @return PaymentTransaction|null
     * @throws QueryFilterInvalidParamException
     */
    public function getByMerchantReference(string $reference) : ?PaymentTransaction
    {
        $queryFilter = new QueryFilter();
        // orderBy is set to DESC to fetch the last transaction from the database
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('merchantReference', Operators::EQUALS, $reference)->orderBy('createdAtTimestamp', QueryFilter::ORDER_DESC);
        /** @var PaymentTransactionEntity| null $entity */
        $entity = $this->repository->selectOne($queryFilter);
        return $entity ? $entity->getPaymentTransaction() : null;
    }
    private function getPaymentTransactionEntity(?PaymentId $paymentId, ?string $returnHmac = null, ?string $paymentLinkId = null) : ?PaymentTransactionEntity
    {
        if (!$paymentId && !$paymentLinkId) {
            return null;
        }
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId());
        if (null !== $paymentId) {
            $queryFilter->where('transactionId', Operators::EQUALS, $paymentId->getTransactionId());
        }
        if (null !== $returnHmac) {
            $queryFilter->where('returnHmac', Operators::EQUALS, $returnHmac);
        }
        if (null !== $paymentLinkId) {
            $queryFilter->where('paymentLinkId', Operators::EQUALS, $paymentLinkId);
        }
        /** @var PaymentTransactionEntity|null $entity */
        $entity = $this->repository->selectOne($queryFilter);
        return $entity;
    }
    public function lockOrderCreation(?PaymentId $paymentId) : bool
    {
        $entity = $this->getPaymentTransactionEntity($paymentId);
        if (null === $entity) {
            return \false;
        }
        $queryFilter = new QueryFilter();
        $lockTimestampCutoff = $this->timeProvider->getMicroTimestamp() - 30;
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('transactionId', Operators::EQUALS, $paymentId->getTransactionId())->where('lockVersion', Operators::EQUALS, $entity->getLockVersion())->where('lockTimestamp', Operators::LESS_THAN, $lockTimestampCutoff);
        $entity->setLockVersion($entity->getLockVersion() + 1);
        $entity->setLockTimestamp($this->timeProvider->getMicroTimestamp());
        $this->repository->update($entity, $queryFilter);
        // Make sure that actual lock version and time match what is expected for successful lock
        $this->timeProvider->sleep(1);
        $entityAfterLock = $this->getPaymentTransactionEntity($paymentId);
        if (null === $entityAfterLock || \abs($entityAfterLock->getLockTimestamp() - $entity->getLockTimestamp()) > 1.0E-5 || $entityAfterLock->getLockVersion() !== $entity->getLockVersion()) {
            return \false;
        }
        return \true;
    }
    public function unlockOrderCreation(?PaymentId $paymentId) : bool
    {
        $entity = $this->getPaymentTransactionEntity($paymentId);
        if (null === $entity) {
            return \false;
        }
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('transactionId', Operators::EQUALS, $paymentId->getTransactionId())->where('lockVersion', Operators::EQUALS, $entity->getLockVersion());
        $entity->setLockVersion($entity->getLockVersion() + 1);
        $entity->setLockTimestamp(0);
        $this->repository->update($entity, $queryFilter);
        // Make sure that actual lock version and time match what is expected for successful lock
        $entityAfterUnlock = $this->getPaymentTransactionEntity($paymentId);
        if (null === $entityAfterUnlock || \abs($entityAfterUnlock->getLockTimestamp() - $entity->getLockTimestamp()) > 1.0E-5 || $entityAfterUnlock->getLockVersion() !== $entity->getLockVersion()) {
            return \false;
        }
        return \true;
    }
}
