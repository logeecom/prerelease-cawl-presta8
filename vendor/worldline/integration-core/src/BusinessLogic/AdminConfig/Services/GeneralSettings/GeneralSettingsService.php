<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings;

use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\CardsSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PayByLinkSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PaymentSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\AutomaticCapture;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidAutomaticCaptureValueException;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidLogRecordsLifetimeException;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPaymentAttemptsNumberException;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\GeneralSettingsResponse;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogRecordsLifetime;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAttemptsNumber;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;

/**
 * Class GeneralSettingsService
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings
 */
class GeneralSettingsService
{
    protected ConnectionConfigRepositoryInterface $connectionConfigRepository;
    protected CardsSettingsRepositoryInterface $cardsSettingsRepository;
    protected LogSettingsRepositoryInterface $logSettingsRepository;
    protected PaymentSettingsRepositoryInterface $paymentSettingsRepository;
    protected StoreService $storeService;
    protected PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository;

    /**
     * @param ConnectionConfigRepositoryInterface $connectionConfigRepository
     * @param CardsSettingsRepositoryInterface $cardsSettingsRepository
     * @param LogSettingsRepositoryInterface $logSettingsRepository
     * @param PaymentSettingsRepositoryInterface $paymentSettingsRepository
     * @param StoreService $storeService
     * @param PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository
     */
    public function __construct(
        ConnectionConfigRepositoryInterface $connectionConfigRepository,
        CardsSettingsRepositoryInterface $cardsSettingsRepository,
        LogSettingsRepositoryInterface $logSettingsRepository,
        PaymentSettingsRepositoryInterface $paymentSettingsRepository,
        StoreService $storeService,
        PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository
    ) {
        $this->connectionConfigRepository = $connectionConfigRepository;
        $this->cardsSettingsRepository = $cardsSettingsRepository;
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
    public function getGeneralSettings(): GeneralSettingsResponse
    {
        $connectionSettings = $this->connectionConfigRepository->getConnection();
        $cardSettings = $this->getCardsSettings();
        $paymentSettings = $this->getPaymentSettings();
        $logSettings = $this->getLogSettings();
        $payByLinkSettings = $this->getPayByLinkSettings();

        return new GeneralSettingsResponse(
            $connectionSettings,
            $cardSettings,
            $paymentSettings,
            $logSettings,
            $payByLinkSettings
        );
    }

    /**
     * @return CardsSettings
     */
    public function getCardsSettings(): CardsSettings
    {
        $savedSettings = $this->cardsSettingsRepository->getCardsSettings();

        return $savedSettings ?: new CardsSettings();
    }

    /**
     * @param CardsSettings $cardsSettings
     *
     * @return void
     */
    public function saveCardsSettings(CardsSettings $cardsSettings): void
    {
        $this->cardsSettingsRepository->saveCardsSettings($cardsSettings);
    }

    /**
     * @return PaymentSettings
     *
     * @throws InvalidAutomaticCaptureValueException
     * @throws InvalidPaymentAttemptsNumberException
     */
    public function getPaymentSettings(): PaymentSettings
    {
        $savedSettings = $this->paymentSettingsRepository->getPaymentSettings();

        if ($savedSettings) {
            return $savedSettings;
        }

        $defaultMapping = $this->storeService->getDefaultOrderStatusMapping();

        return new PaymentSettings(
            PaymentAction::authorizeCapture(),
            AutomaticCapture::create(-1),
            PaymentAttemptsNumber::create(10),
            false,
            $defaultMapping->getPaymentCapturedStatus(),
            $defaultMapping->getPaymentErrorStatus(),
            $defaultMapping->getPaymentPendingStatus(),
            $defaultMapping->getPaymentAuthorizedStatus(),
            $defaultMapping->getPaymentCancelledStatus(),
            $defaultMapping->getPaymentRefundedStatus()
        );
    }

    /**
     * @param PaymentSettings $paymentSettings
     *
     * @return void
     */
    public function savePaymentSettings(PaymentSettings $paymentSettings): void
    {
        $this->paymentSettingsRepository->savePaymentSettings($paymentSettings);
    }

    /**
     * @return LogSettings
     *
     * @throws InvalidLogRecordsLifetimeException
     */
    public function getLogSettings(): LogSettings
    {
        $savedSettings = $this->logSettingsRepository->getLogSettings();

        return  $savedSettings ?: new LogSettings(
            false,
            LogRecordsLifetime::create(14)
        );
    }

    /**
     * @param LogSettings $logSettings
     *
     * @return void
     */
    public function saveLogSettings(LogSettings $logSettings): void
    {
        $this->logSettingsRepository->saveLogSettings($logSettings);
    }

    /**
     * @return PayByLinkSettings
     */
    public function getPayByLinkSettings(): PayByLinkSettings
    {
        $savedSettings = $this->payByLinkSettingsRepository->getPayByLinkSettings();

        return $savedSettings ?: new PayByLinkSettings();
    }

    /**
     * @param PayByLinkSettings $payByLinkSettings
     *
     * @return void
     */
    public function savePayByLinkSettings(PayByLinkSettings $payByLinkSettings): void
    {
        $this->payByLinkSettingsRepository->savePayByLinkSettings($payByLinkSettings);
    }
}
