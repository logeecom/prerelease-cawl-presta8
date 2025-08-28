<?php

namespace OnlinePayments\Core\Infrastructure\TaskExecution;

use OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use OnlinePayments\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use OnlinePayments\Core\Infrastructure\Http\HttpClient;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use OnlinePayments\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\Singleton;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\Runnable;
use OnlinePayments\Core\Infrastructure\Utility\GuidProvider;
use Exception;

/**
 * Class AsyncProcessStarter.
 *
 * @package OnlinePayments\Core\Infrastructure\TaskExecution
 */
class AsyncProcessStarterService extends Singleton implements AsyncProcessService
{
    /**
     * Singleton instance of this class.
     *
     * @var ?Singleton
     */
    protected static ?Singleton $instance = null;

    /**
     * Configuration instance.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * Process entity repository.
     *
     * @var RepositoryInterface
     */
    private RepositoryInterface $processRepository;

    /**
     * GUID provider instance.
     *
     * @var GuidProvider
     */
    private $guidProvider;

    /**
     * HTTP client.
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * AsyncProcessStarterService constructor.
     * @throws RepositoryNotRegisteredException
     */
    protected function __construct()
    {
        parent::__construct();

        $this->httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
        $this->guidProvider = ServiceRegister::getService(GuidProvider::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $this->processRepository = RepositoryRegistry::getRepository(Process::CLASS_NAME);
    }

    /**
     * Starts given runner asynchronously (in new process/web request or similar).
     *
     * @param Runnable $runner Runner that should be started async.
     *
     * @throws HttpRequestException
     * @throws ProcessStarterSaveException
     */
    public function start(Runnable $runner)
    {
        $guid = trim($this->guidProvider->generateGuid());

        $this->saveGuidAndRunner($guid, $runner);
        $this->startRunnerAsynchronously($guid);
    }

    /**
     * Runs a process with provided identifier.
     *
     * @param string $guid Identifier of process.
     */
    public function runProcess(string $guid)
    {
        try {
            $filter = new QueryFilter();
            $filter->where('guid', '=', $guid);

            /** @var Process $process */
            $process = $this->processRepository->selectOne($filter);
            if ($process !== null) {
                $process->getRunner()->run();
                $this->processRepository->delete($process);
            }
        } catch (Exception $e) {
            Logger::logError($e->getMessage(), 'Core', ['guid' => $guid, 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Saves runner and guid to storage.
     *
     * @param string $guid Unique process identifier.
     * @param Runnable $runner Runner instance.
     *
     * @throws ProcessStarterSaveException
     */
    private function saveGuidAndRunner(string $guid, Runnable $runner)
    {
        try {
            $process = new Process();
            $process->setGuid($guid);
            $process->setRunner($runner);

            $this->processRepository->save($process);
        } catch (Exception $e) {
            Logger::logError($e->getMessage());
            throw new ProcessStarterSaveException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Starts runnable asynchronously.
     *
     * @param string $guid Unique process identifier.
     *
     * @throws HttpRequestException
     */
    private function startRunnerAsynchronously(string $guid)
    {
        try {
            $this->httpClient->requestAsync(
                $this->configuration->getAsyncProcessCallHttpMethod(),
                $this->configuration->getAsyncProcessUrl($guid)
            );
        } catch (Exception $e) {
            Logger::logError($e->getMessage(), 'Integration');
            throw new HttpRequestException($e->getMessage(), 0, $e);
        }
    }
}
