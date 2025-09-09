<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\OrderStatusMapping;

use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\GeneralSettingsService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidAutomaticCaptureValueException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPaymentAttemptsNumberException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
/**
 * Class StatusMappingService
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\OrderStatusMapping
 * @internal
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
    public function getStatusMapping(StatusCode $statusCode) : string
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
