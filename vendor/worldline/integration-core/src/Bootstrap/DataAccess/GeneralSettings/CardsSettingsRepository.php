<?php

namespace OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings;

use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\CardsSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\CardsSettingsRepositoryInterface as DomainCardsSettingsRepositoryInterface;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use OnlinePayments\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Class CardSettingsRepository
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings
 */
class CardsSettingsRepository implements CardsSettingsRepositoryInterface, DomainCardsSettingsRepositoryInterface
{
    private RepositoryInterface $repository;
    private StoreContext $storeContext;
    private ActiveConnectionProvider $activeConnectionProvider;

    /**
     * @param RepositoryInterface $repository
     * @param StoreContext $storeContext
     * @param ActiveConnectionProvider $activeConnectionProvider
     */
    public function __construct(
        RepositoryInterface      $repository,
        StoreContext             $storeContext,
        ActiveConnectionProvider $activeConnectionProvider
    )
    {
        $this->repository = $repository;
        $this->storeContext = $storeContext;
        $this->activeConnectionProvider = $activeConnectionProvider;
    }

    /**
     * @inheritDoc
     */
    public function getCardsSettings(): ?CardsSettings
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return null;
        }

        /** @var CardsSettingsEntity | null $settings */
        $settings = $this->repository->selectOne($this->getBaseQuery());

        return $settings ? $settings->getCardsSettings() : null;
    }

    /**
     * @inheritDoc
     */
    public function saveCardsSettings(CardsSettings $cardsSettings): void
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return;
        }

        /** @var CardsSettingsEntity | null $settings */
        $existingEntity = $this->repository->selectOne($this->getBaseQuery());

        if ($existingEntity) {
            $existingEntity->setCardsSettings($cardsSettings);
            $this->repository->update($existingEntity);

            return;
        }

        $entity = new CardsSettingsEntity();
        $entity->setStoreId($this->storeContext->getStoreId());
        $entity->setMode($activeConnection->getMode());
        $entity->setCardsSettings($cardsSettings);
        $this->repository->save($entity);
    }

    private function getBaseQuery(): QueryFilter
    {
        $activeConnection = $this->activeConnectionProvider->get();
        $mode = $activeConnection ? $activeConnection->getMode() : null;

        $queryFilter = new QueryFilter();

        return $queryFilter
            ->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())
            ->where('mode', Operators::EQUALS, (string)$mode);
    }

    /**
     * @param string $mode
     *
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function deleteByMode(string $mode): void
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())
            ->where('mode', Operators::EQUALS, (string)$mode);

        $this->repository->deleteWhere($queryFilter);
    }
}
