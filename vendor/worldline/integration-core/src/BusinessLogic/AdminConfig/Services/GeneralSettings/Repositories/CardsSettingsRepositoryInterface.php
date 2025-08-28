<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;

/**
 * Interface CardsSettingsRepositoryInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Repositories
 */
interface CardsSettingsRepositoryInterface
{
    /**
     * @return CardsSettings|null
     */
    public function getCardsSettings(): ?CardsSettings;

    /**
     * @param CardsSettings $cardsSettings
     *
     * @return void
     */
    public function saveCardsSettings(CardsSettings $cardsSettings): void;

    /**
     * @param string $mode
     *
     * @return void
     */
    public function deleteByMode(string $mode): void;
}
