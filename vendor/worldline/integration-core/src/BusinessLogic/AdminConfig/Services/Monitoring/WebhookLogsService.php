<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Monitoring;

use DateTime;
use Exception;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect\Repositories\DisconnectRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\WebhookLog;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\WebhookStatuses;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodDefaultConfigs;
use OnlinePayments\Core\BusinessLogic\Domain\Webhook\WebhookData;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentsProxyInterface;

/**
 * Class WebhookLogsService
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Monitoring
 */
class WebhookLogsService
{
    protected WebhookLogRepositoryInterface $repository;
    protected PaymentsProxyInterface $paymentsProxy;
    protected DisconnectRepositoryInterface $disconnectRepository;
    protected ActiveBrandProviderInterface $activeBrandProvider;

    /**
     * @param WebhookLogRepositoryInterface $repository
     * @param PaymentsProxyInterface $paymentsProxy
     * @param DisconnectRepositoryInterface $disconnectRepository
     * @param ActiveBrandProviderInterface $activeBrandProvider
     */
    public function __construct(
        WebhookLogRepositoryInterface $repository,
        PaymentsProxyInterface $paymentsProxy,
        DisconnectRepositoryInterface $disconnectRepository,
        ActiveBrandProviderInterface $activeBrandProvider
    ) {
        $this->repository = $repository;
        $this->paymentsProxy = $paymentsProxy;
        $this->disconnectRepository = $disconnectRepository;
        $this->activeBrandProvider = $activeBrandProvider;
    }

    /**
     * @param WebhookData $webhookData
     *
     * @return void
     *
     * @throws Exception
     */
    public function logWebhook(WebhookData $webhookData): void
    {
        $webhookLog = new WebhookLog(
            $webhookData->getMerchantReference(),
            $webhookData->getId(),
            PaymentMethodDefaultConfigs::getName(
                $webhookData->getId(), $this->activeBrandProvider->getActiveBrand()->getPaymentMethodName()
            )['translation'] ?? '',
            WebhookStatuses::statusMap[$webhookData->getStatusCategory()],
            $webhookData->getType(),
            new DateTime($webhookData->getCreated()),
            $webhookData->getStatusCode(),
            $webhookData->getWebhookBody(),
            $this->activeBrandProvider->getTransactionUrl() . PaymentId::parse((string)$webhookData->getId())->getTransactionId()
        );

        $this->repository->saveWebhookLog($webhookLog);
    }

    /**
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $searchTerm
     *
     * @return array
     */
    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm): array
    {
        return $this->repository->getWebhookLogs($pageNumber, $pageSize, $searchTerm);
    }

    /**
     * @return array
     */
    public function getAllLogs(): array
    {
        $logs = $this->repository->getAllLogs();
        $result = [];

        foreach ($logs as $log) {
            $result[] = $log->toArray();
        }

        return $result;
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    public function count(): int
    {
        $disconnectTime = $this->disconnectRepository->getDisconnectTime();

        return $this->repository->count($disconnectTime);
    }
}
