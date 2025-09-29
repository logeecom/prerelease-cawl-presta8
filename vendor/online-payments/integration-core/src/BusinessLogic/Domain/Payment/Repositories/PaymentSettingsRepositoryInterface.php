<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
/**
 * Interface PaymentSettingsRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories
 */
interface PaymentSettingsRepositoryInterface
{
    public function getPaymentSettings() : ?PaymentSettings;
}
