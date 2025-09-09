<?php

namespace CAWL\OnlinePayments\Core\Infrastructure\TaskExecution;

use CAWL\OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerManager as BaseService;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
/**
 * Class TaskRunnerManager.
 *
 * @package OnlinePayments\Core\Infrastructure\TaskExecution
 * @internal
 */
class TaskRunnerManager implements BaseService
{
    /**
     * @var ?Configuration
     */
    protected ?Configuration $configuration = null;
    /**
     * @var ?TaskRunnerWakeup
     */
    protected ?TaskRunnerWakeup $taskRunnerWakeupService = null;
    /**
     * Halts task runner.
     *
     * @throws QueryFilterInvalidParamException
     */
    public function halt()
    {
        $this->getConfiguration()->setTaskRunnerHalted(\true);
    }
    /**
     * Resumes task execution.
     *
     * @throws QueryFilterInvalidParamException
     */
    public function resume()
    {
        $this->getConfiguration()->setTaskRunnerHalted(\false);
        $this->getTaskRunnerWakeupService()->wakeup();
    }
    /**
     * Retrieves configuration.
     *
     * @return Configuration Configuration instance.
     */
    protected function getConfiguration() : Configuration
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }
        return $this->configuration;
    }
    /**
     * Retrieves task runner wakeup service.
     *
     * @return TaskRunnerWakeup Task runner wakeup instance.
     */
    protected function getTaskRunnerWakeupService() : TaskRunnerWakeup
    {
        if ($this->taskRunnerWakeupService === null) {
            $this->taskRunnerWakeupService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        }
        return $this->taskRunnerWakeupService;
    }
}
