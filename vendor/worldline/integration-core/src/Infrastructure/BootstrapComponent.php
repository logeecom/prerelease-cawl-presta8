<?php

namespace OnlinePayments\Core\Infrastructure;

use OnlinePayments\Core\Infrastructure\Configuration\ConfigurationManager;
use OnlinePayments\Core\Infrastructure\Http\CurlHttpClient;
use OnlinePayments\Core\Infrastructure\Http\HttpClient;
use OnlinePayments\Core\Infrastructure\TaskExecution\AsyncProcessStarterService;
use OnlinePayments\Core\Infrastructure\TaskExecution\Events\QueueItemStateTransitionEventBus;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerManager;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;
use OnlinePayments\Core\Infrastructure\TaskExecution\RunnerStatusStorage;
use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunner;
use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use OnlinePayments\Core\Infrastructure\Utility\Events\EventBus;
use OnlinePayments\Core\Infrastructure\Utility\GuidProvider;
use OnlinePayments\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class BootstrapComponent.
 *
 * @package OnlinePayments\Core\Infrastructure
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
        ServiceRegister::registerService(
            ConfigurationManager::CLASS_NAME,
            function () {
                return ConfigurationManager::getInstance();
            }
        );
        ServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () {
                return TimeProvider::getInstance();
            }
        );
        ServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () {
                return GuidProvider::getInstance();
            }
        );
        ServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new CurlHttpClient();
            }
        );

        ServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );

        ServiceRegister::registerService(
            AsyncProcessService::CLASS_NAME,
            function () {
                return AsyncProcessStarterService::getInstance();
            }
        );

        ServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () {
                return new QueueService();
            }
        );

        ServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TaskRunnerWakeupService();
            }
        );

        ServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () {
                return new TaskRunner();
            }
        );

        ServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () {
                return new RunnerStatusStorage();
            }
        );

        ServiceRegister::registerService(
            TaskRunnerManager::CLASS_NAME,
            function () {
                return new TaskExecution\TaskRunnerManager();
            }
        );

        ServiceRegister::registerService(
            QueueItemStateTransitionEventBus::CLASS_NAME,
            function () {
                return QueueItemStateTransitionEventBus::getInstance();
            }
        );
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
