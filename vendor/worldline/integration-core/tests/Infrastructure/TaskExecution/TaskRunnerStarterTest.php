<?php

namespace OnlinePayments\Core\Tests\Infrastructure\TaskExecution;

use OnlinePayments\Core\Infrastructure\Http\HttpClient;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\Serializer\Serializer;
use OnlinePayments\Core\Infrastructure\TaskExecution\AsyncProcessStarterService;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use OnlinePayments\Core\Infrastructure\TaskExecution\Process;
use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunner;
use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunnerStarter;
use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunnerStatus;
use OnlinePayments\Core\Infrastructure\Utility\GuidProvider;
use OnlinePayments\Core\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestRunnerStatusStorage;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunner;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\TestGuidProvider;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestServiceRegister;
use Exception;

/**
 * Class TaskRunnerStarterTest
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\TaskExecution
 */
class TaskRunnerStarterTest extends BaseInfrastructureTestWithServices
{
    /** @var TestTaskRunner */
    private $taskRunner;
    /** @var TestRunnerStatusStorage */
    private $runnerStatusStorage;
    /** @var TaskRunnerStarter */
    private $runnerStarter;
    /** @var string */
    private $guid;

    public function testTaskRunnerIsStartedWithProperGuid()
    {
        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

    /**
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     * @throws Exception
     */
    public function testRunningTaskRunnerWhenExpired()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $expiredTimestamp = $currentTimestamp - TaskRunnerStatus::MAX_ALIVE_TIME - 1;
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $expiredTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner is expired.');
        $this->assertStringContainsString(
            'Failed to run task runner',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner is expired'
        );
        $this->assertStringContainsString(
            'Runner is expired.',
            $this->shopLogger->data->getMessage(),
            'Debug message must be logged when trying to run expired task runner.'
        );
    }

    /**
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     * @throws Exception
     */
    public function testRunningTaskRunnerWithActiveGuidDoNotMatchGuidGeneratedWithWakeup()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus('different_active_guid', $currentTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner guid is not set as active runner guid.');
        $this->assertStringContainsString(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner guid is not set as active runner guid.'
        );
        $this->assertStringContainsString(
            'Runner guid is not set as active.',
            $this->shopLogger->data->getMessage(),
            'Debug message must be logged when trying to run task runner with guid that is not set as active runner guid.'
        );
    }

    public function testRunWhenRunnerStatusServiceIsUnavailable()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new TaskRunnerStatusStorageUnavailableException('Simulation for unavailable storage exception.')
        );

        // Act
        $this->runnerStarter->run();

        $this->assertStringContainsString(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner status storage is unavailable.'
        );
        $this->assertStringContainsString('Runner status storage unavailable.', $this->shopLogger->data->getMessage());
    }

    public function testRunInCaseOfUnexpectedException()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new Exception('Simulation for unexpected exception.')
        );

        // Act
        $this->runnerStarter->run();
        $this->assertStringContainsString(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when unexpected exception occurs.'
        );
        $this->assertStringContainsString('Unexpected error occurred.', $this->shopLogger->data->getMessage());
    }

    public function testTaskStarterMustBeRunnableAfterDeserialization()
    {
        // Arrange
        /** @var TaskRunnerStarter $unserializedRunnerStarter */
        $unserializedRunnerStarter = Serializer::unserialize(Serializer::serialize($this->runnerStarter));

        // Act
        $unserializedRunnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

    /**
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(Process::CLASS_NAME, MemoryRepository::getClassName());

        $runnerStatusStorage = new TestRunnerStatusStorage();
        $taskRunner = new TestTaskRunner();
        $guidProvider = TestGuidProvider::getInstance();

        TestServiceRegister::registerService(
            AsyncProcessService::CLASS_NAME,
            function () {
                return AsyncProcessStarterService::getInstance();
            }
        );
        TestServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () use ($runnerStatusStorage) {
                return $runnerStatusStorage;
            }
        );
        TestServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () use ($taskRunner) {
                return $taskRunner;
            }
        );
        TestServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () use ($guidProvider) {
                return $guidProvider;
            }
        );
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new TestHttpClient();
            }
        );
        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TestTaskRunnerWakeupService();
            }
        );

        Logger::resetInstance();

        $this->runnerStatusStorage = $runnerStatusStorage;
        $this->taskRunner = $taskRunner;

        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->guid = 'test_runner_guid';
        $this->runnerStarter = new TaskRunnerStarter($this->guid);
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $currentTimestamp));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        AsyncProcessStarterService::resetInstance();
        MemoryStorage::reset();
        parent::tearDown();
    }
}
