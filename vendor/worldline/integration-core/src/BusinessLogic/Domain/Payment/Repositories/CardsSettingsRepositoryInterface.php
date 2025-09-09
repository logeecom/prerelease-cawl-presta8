<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
/**
 * Interface CardsSettingsRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories
 * @internal
 */
interface CardsSettingsRepositoryInterface
{
    public function getCardsSettings() : ?CardsSettings;
}
