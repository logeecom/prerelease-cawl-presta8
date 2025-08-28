<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;

/**
 * Interface PaymentSettingsRepositoryInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Repositories
 */
interface PaymentSettingsRepositoryInterface
{
    /**
     * @return PaymentSettings|null
     */
    public function getPaymentSettings(): ?PaymentSettings;

    /**
     * @param PaymentSettings $paymentSettings
     *
     * @return void
     */
    public function savePaymentSettings(PaymentSettings $paymentSettings): void;

    /**
     * @param string $mode
     *
     * @return void
     */
    public function deleteByMode(string $mode): void;
}
