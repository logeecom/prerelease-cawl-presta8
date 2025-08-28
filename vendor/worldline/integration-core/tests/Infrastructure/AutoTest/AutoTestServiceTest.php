<?php

namespace OnlinePayments\Core\Tests\Infrastructure\AutoTest;

use OnlinePayments\Core\Infrastructure\AutoTest\AutoTestLogger;
use OnlinePayments\Core\Infrastructure\AutoTest\AutoTestService;
use OnlinePayments\Core\Infrastructure\Exceptions\StorageNotAccessibleException;
use OnlinePayments\Core\Infrastructure\Http\DTO\Options;
use OnlinePayments\Core\Infrastructure\Http\HttpClient;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Logger\LogData;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestHttpClient;

/**
 * Class AutoTestServiceTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\AutoTest
 */
class AutoTestServiceTest extends BaseTestCase
{
    /**
     * @var TestHttpClient
     */
    protected TestHttpClient $httpClient;
    /**
     * @var TestHttpClient
     */
    protected TestHttpClient $logger;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        TestRepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        $me = $this;
        $this->httpClient = new TestHttpClient();
        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();

        TestRepositoryRegistry::cleanUp();
        AutoTestLogger::resetInstance();
    }

    /**
     * Test setting auto-test mode.
     */
    public function testSetAutoTestMode(): void
    {
        TestRepositoryRegistry::registerRepository(LogData::getClassName(), MemoryRepository::getClassName());
        $service = new AutoTestService();
        $service->setAutoTestMode(true);

        $repo = TestRepositoryRegistry::getRepository(LogData::getClassName());
        self::assertNotNull($repo, 'Log repository should be registered.');

        $loggerService = ServiceRegister::getService(ShopLoggerAdapter::CLASS_NAME);
        self::assertNotNull($loggerService, 'Logger service should be registered.');
        self::assertInstanceOf(
            AutoTestLogger::class,
            $loggerService,
            'AutoTestLogger service should be registered.'
        );

        self::assertTrue($this->shopConfig->isAutoTestMode(), 'Auto-test mode should be set.');
    }

    /**
     * Test successful start of the auto-test.
     */
    public function testStartAutoTestSuccess(): void
    {
        TestRepositoryRegistry::registerRepository(LogData::getClassName(), MemoryRepository::getClassName());
        $domain = parse_url($this->shopConfig->getAsyncProcessUrl(''), PHP_URL_HOST);
        $this->shopConfig->setHttpConfigurationOptions($domain, array(new Options('test', 'value')));

        $service = new AutoTestService();
        $queueItemId = $service->startAutoTest();

        self::assertNotNull($queueItemId, 'Test task should be enqueued.');

        $status = $service->getAutoTestTaskStatus($queueItemId);
        self::assertEquals('queued', $status->taskStatus, 'AutoTest tasks should be enqueued.');
        $logger = $this->shopLogger;
        $service->stopAutoTestMode(
            function () use ($logger) {
                return $logger;
            }
        );
        // starting auto-test should produce 2 logs. Additional logs should not be added to the auto-test logs.
        Logger::logInfo('this should not be added to the log');

        $allLogs = AutoTestLogger::getInstance()->getLogs();
        $allLogsArray = AutoTestLogger::getInstance()->getLogsArray();
        self::assertNotEmpty($allLogs, 'Starting logs should be added.');
        self::assertCount(2, $allLogs, 'Additional logs should not be added.');
        self::assertCount(count($allLogs), $allLogsArray, 'ToArray should produce the same number of items.');
        self::assertEquals('Start auto-test', $allLogs[0]->getMessage(), 'Starting logs should be added.');

        $context = $allLogs[1]->getContext();
        self::assertCount(1, $context, 'Current HTTP configuration options should be logged.');
        self::assertEquals($domain, $context[0]->getName(), 'Current HTTP configuration options should be logged.');

        $options = $context[0]->getValue();
        self::assertArrayHasKey(
            'HTTPOptions',
            $options,
            'Current HTTP configuration options should be logged.'
        );

        self::assertCount(1, $options, 'One HTTP configuration options should be set.');
    }

    /**
     * Tests failure when storage is not available.
     */
    public function testStartAutoTestStorageFailure(): void
    {
        $this->expectException(StorageNotAccessibleException::class);

        // repository is not registered
        $service = new AutoTestService();
        $service->startAutoTest();
    }
}
