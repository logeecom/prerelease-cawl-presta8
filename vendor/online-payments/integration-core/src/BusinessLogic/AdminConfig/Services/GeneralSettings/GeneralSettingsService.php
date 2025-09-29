<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings;

use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PayByLinkSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PaymentSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\AutomaticCapture;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidAutomaticCaptureValueException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidLogRecordsLifetimeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPaymentAttemptsNumberException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\GeneralSettingsResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogRecordsLifetime;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAttemptsNumber;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
/**
 * Class GeneralSettingsService
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings
 */
class GeneralSettingsService
{
    protected ConnectionConfigRepositoryInterface $connectionConfigRepository;
    protected LogSettingsRepositoryInterface $logSettingsRepository;
    protected PaymentSettingsRepositoryInterface $paymentSettingsRepository;
    protected StoreService $storeService;
    protected PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository;
    /**
     * @param ConnectionConfigRepositoryInterface $connectionConfigRepository
     * @param LogSettingsRepositoryInterface $logSettingsRepository
     * @param PaymentSettingsRepositoryInterface $paymentSettingsRepository
     * @param StoreService $storeService
     * @param PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository
     */
    public function __construct(ConnectionConfigRepositoryInterface $connectionConfigRepository, LogSettingsRepositoryInterface $logSettingsRepository, PaymentSettingsRepositoryInterface $paymentSettingsRepository, StoreService $storeService, PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository)
    {
        $this->connectionConfigRepository = $connectionConfigRepository;
        $this->logSettingsRepository = $logSettingsRepository;
        $this->paymentSettingsRepository = $paymentSettingsRepository;
        $this->storeService = $storeService;
        $this->payByLinkSettingsRepository = $payByLinkSettingsRepository;
    }
    /**
     * @return GeneralSettingsResponse
     *
     * @throws InvalidAutomaticCaptureValueException
     * @throws InvalidLogRecordsLifetimeException
     * @throws InvalidPaymentAttemptsNumberException
     */
    public function getGeneralSettings() : GeneralSettingsResponse
    {
        $connectionSettings = $this->connectionConfigRepository->getConnection();
        $paymentSettings = $this->getPaymentSettings();
        $logSettings = $this->getLogSettings();
        $payByLinkSettings = $this->getPayByLinkSettings();
        return new GeneralSettingsResponse($connectionSettings, $paymentSettings, $logSettings, $payByLinkSettings);
    }
    /**
     * @return PaymentSettings
     *
     * @throws InvalidAutomaticCaptureValueException
     * @throws InvalidPaymentAttemptsNumberException
     */
    public function getPaymentSettings() : PaymentSettings
    {
        $savedSettings = $this->paymentSettingsRepository->getPaymentSettings();
        if ($savedSettings) {
            return $savedSettings;
        }
        $defaultMapping = $this->storeService->getDefaultOrderStatusMapping();
        return new PaymentSettings(PaymentAction::authorizeCapture(), AutomaticCapture::create(-1), PaymentAttemptsNumber::create(10), \false, $defaultMapping->getPaymentCapturedStatus(), $defaultMapping->getPaymentErrorStatus(), $defaultMapping->getPaymentPendingStatus(), $defaultMapping->getPaymentAuthorizedStatus(), $defaultMapping->getPaymentCancelledStatus(), $defaultMapping->getPaymentRefundedStatus());
    }
    /**
     * @param PaymentSettings $paymentSettings
     *
     * @return void
     */
    public function savePaymentSettings(PaymentSettings $paymentSettings) : void
    {
        $this->paymentSettingsRepository->savePaymentSettings($paymentSettings);
    }
    /**
     * @return LogSettings
     *
     * @throws InvalidLogRecordsLifetimeException
     */
    public function getLogSettings() : LogSettings
    {
        $savedSettings = $this->logSettingsRepository->getLogSettings();
        return $savedSettings ?: new LogSettings(\false, LogRecordsLifetime::create(14));
    }
    /**
     * @param LogSettings $logSettings
     *
     * @return void
     */
    public function saveLogSettings(LogSettings $logSettings) : void
    {
        $this->logSettingsRepository->saveLogSettings($logSettings);
    }
    /**
     * @return PayByLinkSettings
     */
    public function getPayByLinkSettings() : PayByLinkSettings
    {
        $savedSettings = $this->payByLinkSettingsRepository->getPayByLinkSettings();
        return $savedSettings ?: new PayByLinkSettings();
    }
    /**
     * @param PayByLinkSettings $payByLinkSettings
     *
     * @return void
     */
    public function savePayByLinkSettings(PayByLinkSettings $payByLinkSettings) : void
    {
        $this->payByLinkSettingsRepository->savePayByLinkSettings($payByLinkSettings);
    }
}
