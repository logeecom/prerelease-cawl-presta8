<?php

namespace CAWL\OnlinePayments\Core\Infrastructure;

use CAWL\OnlinePayments\Core\Infrastructure\Configuration\ConfigurationManager;
use CAWL\OnlinePayments\Core\Infrastructure\Http\CurlHttpClient;
use CAWL\OnlinePayments\Core\Infrastructure\Http\HttpClient;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\AsyncProcessStarterService;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Events\QueueItemStateTransitionEventBus;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerManager;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\RunnerStatusStorage;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunner;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use CAWL\OnlinePayments\Core\Infrastructure\Utility\Events\EventBus;
use CAWL\OnlinePayments\Core\Infrastructure\Utility\GuidProvider;
use CAWL\OnlinePayments\Core\Infrastructure\Utility\TimeProvider;
/**
 * Class BootstrapComponent.
 *
 * @package OnlinePayments\Core\Infrastructure
 * @internal
 */
class BootstrapComponent
{
    /**
     * Initializes infrastructure components.
     */
    public static function init()
    {
        static::initServices();
        static::initRepositories();
        static::initEvents();
    }
    /**
     * Initializes services and utilities.
     */
    protected static function initServices()
    {
        ServiceRegister::registerService(ConfigurationManager::CLASS_NAME, function () {
            return ConfigurationManager::getInstance();
        });
        ServiceRegister::registerService(TimeProvider::CLASS_NAME, function () {
            return TimeProvider::getInstance();
        });
        ServiceRegister::registerService(GuidProvider::CLASS_NAME, function () {
            return GuidProvider::getInstance();
        });
        ServiceRegister::registerService(EventBus::CLASS_NAME, function () {
            return EventBus::getInstance();
        });
        ServiceRegister::registerService(HttpClient::CLASS_NAME, function () {
            return new CurlHttpClient();
        });
        ServiceRegister::registerService(EventBus::CLASS_NAME, function () {
            return EventBus::getInstance();
        });
        ServiceRegister::registerService(AsyncProcessService::CLASS_NAME, function () {
            return AsyncProcessStarterService::getInstance();
        });
        ServiceRegister::registerService(QueueService::CLASS_NAME, function () {
            return new QueueService();
        });
        ServiceRegister::registerService(TaskRunnerWakeup::CLASS_NAME, function () {
            return new TaskRunnerWakeupService();
        });
        ServiceRegister::registerService(TaskRunner::CLASS_NAME, function () {
            return new TaskRunner();
        });
        ServiceRegister::registerService(TaskRunnerStatusStorage::CLASS_NAME, function () {
            return new RunnerStatusStorage();
        });
        ServiceRegister::registerService(TaskRunnerManager::CLASS_NAME, function () {
            return new TaskExecution\TaskRunnerManager();
        });
        ServiceRegister::registerService(QueueItemStateTransitionEventBus::CLASS_NAME, function () {
            return QueueItemStateTransitionEventBus::getInstance();
        });
    }
    /**
     * Initializes repositories.
     */
    protected static function initRepositories()
    {
    }
    /**
     * Initializes events.
     */
    protected static function initEvents()
    {
    }
}
