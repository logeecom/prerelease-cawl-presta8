<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;

/**
 * Interface CardsSettingsRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories
 */
interface CardsSettingsRepositoryInterface
{
    public function getCardsSettings(): ?CardsSettings;
}