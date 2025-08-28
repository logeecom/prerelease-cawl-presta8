<?php
/** @noinspection PhpDuplicateArrayKeysInspection */

namespace OnlinePayments\Core\Tests\Infrastructure\TaskExecution;

use OnlinePayments\Core\Infrastructure\Configuration\ConfigEntity;
use OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use OnlinePayments\Core\Infrastructure\Configuration\ConfigurationManager;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use OnlinePayments\Core\Infrastructure\TaskExecution\RunnerStatusStorage;
use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunnerStatus;
use OnlinePayments\Core\Infrastructure\Utility\TimeProvider;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestConfigurationManager;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestServiceRegister;
use Exception;
use PHPUnit\Framework\TestCase;

class TaskRunnerStatusStorageTest extends TestCase
{
    /** @var TestShopConfiguration */
    private $configuration;

    /**
     *
     * @throws RepositoryClassException
     */
    protected function setUp(): void
    {
        $configuration = new TestShopConfiguration();

        new TestServiceRegister(
            array(
                ConfigurationManager::CLASS_NAME => function () {
                    return new TestConfigurationManager();
                },
                TimeProvider::CLASS_NAME => function () {
                    return new TestTimeProvider();
                },
                DefaultLoggerAdapter::CLASS_NAME => function () {
                    return new TestDefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function () {
                    return new TestShopLogger();
                },
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                },
            )
        );

        $this->configuration = $configuration;

        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * @throws TaskRunnerStatusStorageUnavailableException|QueryFilterInvalidParamException
     */
    public function testSetTaskRunnerWhenItExist()
    {
        $taskRunnerStatusStorage = new RunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid', 123456789);
        $ex = null;

        try {
            $taskRunnerStatusStorage->setStatus($taskStatus);
        } catch (Exception $ex) {
            $this->fail('Set task runner status storage should not throw exception.');
        }

        $this->assertEmpty($ex);
    }

    /**
     * @throws TaskRunnerStatusStorageUnavailableException|QueryFilterInvalidParamException
     */
    public function testSetTaskRunnerWhenItExistButItIsNotTheSame()
    {
        $this->expectException(TaskRunnerStatusChangeException::class);

        $taskRunnerStatusStorage = new RunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid2', 123456789);

        $taskRunnerStatusStorage->setStatus($taskStatus);
    }
}
