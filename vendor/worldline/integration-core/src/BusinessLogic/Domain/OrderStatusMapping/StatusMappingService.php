<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\OrderStatusMapping;

use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\GeneralSettingsService;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidAutomaticCaptureValueException;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPaymentAttemptsNumberException;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;

/**
 * Class StatusMappingService
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\OrderStatusMapping
 */
class StatusMappingService
{
    protected GeneralSettingsService $generalSettingsService;

    /**
     * @param GeneralSettingsService $generalSettingsService
     */
    public function __construct(GeneralSettingsService $generalSettingsService)
    {
        $this->generalSettingsService = $generalSettingsService;
    }

    /**
     * @param StatusCode $statusCode
     * @return string
     * @throws InvalidAutomaticCaptureValueException
     * @throws InvalidPaymentAttemptsNumberException
     */
    public function getStatusMapping(StatusCode $statusCode): string
    {
        $mapping = $this->generalSettingsService->getPaymentSettings();

        if ($statusCode->equals(StatusCode::completed())) {
            return $mapping->getPaymentCapturedStatus();
        }

        if ($statusCode->equals(StatusCode::authorized())) {
            return $mapping->getPaymentAuthorizedStatus();
        }

        if ($statusCode->isCanceledOrRejected()) {
            return $mapping->getPaymentCancelledStatus();
        }

        if ($statusCode->isRefunded()) {
            return $mapping->getPaymentRefundedStatus();
        }

        if ($statusCode->isPending()) {
            return $mapping->getPaymentPendingStatus();
        }

        return '';
    }
}
