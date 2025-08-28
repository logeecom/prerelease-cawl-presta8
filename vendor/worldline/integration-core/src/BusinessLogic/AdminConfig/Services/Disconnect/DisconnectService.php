<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect;

use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\CardsSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PayByLinkSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PaymentSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Disconnect\DisconnectTaskEnqueuerInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories\PaymentConfigRepositoryInterface;

/**
 * Class DisconnectService
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect
 */
class DisconnectService
{
    protected ShopPaymentService $shopPaymentService;
    protected ConnectionConfigRepositoryInterface $connectionConfigRepository;
    protected CardsSettingsRepositoryInterface $cardsSettingsRepository;
    protected PaymentSettingsRepositoryInterface $paymentSettingsRepository;
    protected LogSettingsRepositoryInterface $logSettingsRepository;
    protected PaymentConfigRepositoryInterface $paymentMethodConfigRepository;
    protected PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository;
    protected DisconnectTaskEnqueuerInterface $disconnectTaskEnqueuer;

    /**
     * @param ShopPaymentService $shopPaymentService
     * @param ConnectionConfigRepositoryInterface $connectionConfigRepository
     * @param CardsSettingsRepositoryInterface $cardsSettingsRepository
     * @param PaymentSettingsRepositoryInterface $paymentSettingsRepository
     * @param LogSettingsRepositoryInterface $logSettingsRepository
     * @param PaymentConfigRepositoryInterface $paymentMethodConfigRepository
     * @param PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository
     * @param DisconnectTaskEnqueuerInterface $disconnectTaskEnqueuer
     */
    public function __construct(
        ShopPaymentService $shopPaymentService,
        ConnectionConfigRepositoryInterface $connectionConfigRepository,
        CardsSettingsRepositoryInterface $cardsSettingsRepository,
        PaymentSettingsRepositoryInterface $paymentSettingsRepository,
        LogSettingsRepositoryInterface $logSettingsRepository,
        PaymentConfigRepositoryInterface $paymentMethodConfigRepository,
        PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository,
        DisconnectTaskEnqueuerInterface $disconnectTaskEnqueuer
    ) {
        $this->shopPaymentService = $shopPaymentService;
        $this->connectionConfigRepository = $connectionConfigRepository;
        $this->cardsSettingsRepository = $cardsSettingsRepository;
        $this->paymentSettingsRepository = $paymentSettingsRepository;
        $this->logSettingsRepository = $logSettingsRepository;
        $this->paymentMethodConfigRepository = $paymentMethodConfigRepository;
        $this->payByLinkSettingsRepository = $payByLinkSettingsRepository;
        $this->disconnectTaskEnqueuer = $disconnectTaskEnqueuer;
    }

    public function disconnect(): void
    {
        try {
            $this->disconnectIntegration();
            $this->deleteAllData();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return void
     */
    public function disconnectIntegration(): void
    {
        $activeConnection = $this->connectionConfigRepository->getConnection();
        if (null === $activeConnection) {
            return;
        }

        $this->shopPaymentService->deletePaymentMethods($activeConnection->getMode());
        $this->cardsSettingsRepository->deleteByMode($activeConnection->getMode());
        $this->paymentSettingsRepository->deleteByMode($activeConnection->getMode());
        $this->logSettingsRepository->deleteByMode($activeConnection->getMode());
        $this->paymentMethodConfigRepository->deleteByMode($activeConnection->getMode());
        $this->payByLinkSettingsRepository->deleteByMode($activeConnection->getMode());
        $this->connectionConfigRepository->disconnect();
    }

    public function deleteAllData(): void
    {
        $this->disconnectTaskEnqueuer->enqueueDisconnectTask();
    }
}
