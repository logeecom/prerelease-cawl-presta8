<?php

namespace OnlinePayments\Core\Infrastructure\AutoTest;

use OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use OnlinePayments\Core\Infrastructure\Exceptions\StorageNotAccessibleException;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Logger\LogData;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;
use Exception;

/**
 * Class AutoTestService.
 *
 * @package OnlinePayments\Core\Infrastructure\AutoTest
 */
class AutoTestService
{
    private ?Configuration $configService = null;

    /**
     * Starts the auto-test.
     *
     * @return int|null The queue item ID.
     *
     * @throws StorageNotAccessibleException
     * @throws QueueStorageUnavailableException
     */
    public function startAutoTest(): ?int
    {
        try {
            $this->setAutoTestMode(true);
            $this->deletePreviousLogs();
            Logger::logInfo('Start auto-test');
        } catch (Exception $e) {
            throw new StorageNotAccessibleException('Cannot start the auto-test because storage is not accessible.');
        }

        $this->logHttpOptions();

        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        $queueItem = $queueService->enqueue('Auto-test', new AutoTestTask('DUMMY TEST DATA'));

        return $queueItem->getId();
    }

    /**
     * Activates the auto-test mode and registers the necessary components.
     *
     * @param bool $persist Indicates whether to store the mode change in configuration.
     */
    public function setAutoTestMode(bool $persist = false): void
    {
        Logger::resetInstance();
        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return AutoTestLogger::getInstance();
            }
        );

        if ($persist) {
            $this->getConfigService()->setAutoTestMode(true);
        }
    }

    /**
     * Gets the status of the auto-test task.
     *
     * @param int $queueItemId The ID of the queue item that started the task.
     *
     * @return AutoTestStatus The status of the auto-test task.
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function getAutoTestTaskStatus(int $queueItemId = 0): AutoTestStatus
    {
        $this->setAutoTestMode();

        $filter = new QueryFilter();
        if ($queueItemId) {
            $filter->where('id', Operators::EQUALS, $queueItemId);
        } else {
            $filter->where('taskType', Operators::EQUALS, 'AutoTestTask');
            $filter->orderBy('queueTime', 'DESC');
        }

        $status = '';
        $item = RepositoryRegistry::getQueueItemRepository()->selectOne($filter);
        if ($item) {
            if ($item->getStatus() === QueueItem::QUEUED && $item->getQueueTimestamp() < time() - 30) {
                // if item is queued and task runner did not start it within 30 seconds, task expired
                Logger::logError('Auto-test task did not finish within expected time frame.');

                $status = 'timeout';
            } else {
                $status = $item->getStatus();
            }
        }

        return new AutoTestStatus(
            $status,
            in_array($status, array('timeout', QueueItem::COMPLETED, QueueItem::FAILED), true),
            $status === 'timeout' ? 'Task could not be started.' : '',
            AutoTestLogger::getInstance()->getLogs()
        );
    }

    /**
     * Resets the auto-test mode.
     * When auto-test finishes, this is needed to reset the flag in configuration service and
     * re-initialize shop logger. Otherwise, logs and async calls will still use auto-test mode.
     *
     * @param callable $loggerInitializerDelegate Delegate that will give instance of the shop logger service.
     */
    public function stopAutoTestMode(callable $loggerInitializerDelegate): void
    {
        $this->getConfigService()->setAutoTestMode(false);
        ServiceRegister::registerService(ShopLoggerAdapter::CLASS_NAME, $loggerInitializerDelegate);
        Logger::resetInstance();
    }

    /**
     * Deletes previous auto-test logs.
     *
     * @throws RepositoryNotRegisteredException
     */
    protected function deletePreviousLogs(): void
    {
        $repo = RepositoryRegistry::getRepository(LogData::getClassName());
        $logs = $repo->select();
        foreach ($logs as $log) {
            $repo->delete($log);
        }
    }

    /**
     * Logs current HTTP configuration options.
     */
    protected function logHttpOptions(): void
    {
        $testDomain = parse_url($this->getConfigService()->getAsyncProcessUrl(''), PHP_URL_HOST);
        $options = array();
        foreach ($this->getConfigService()->getHttpConfigurationOptions($testDomain) as $option) {
            $options[$option->getName()] = $option->getValue();
        }

        Logger::logInfo('HTTP configuration options', 'Core', [$testDomain => ['HTTPOptions' => $options]]);
    }

    /**
     * Gets the configuration service instance.
     *
     * @return Configuration Configuration service instance.
     */
    private function getConfigService(): Configuration
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
