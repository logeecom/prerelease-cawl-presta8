<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunnerWakeupService;

/**
 * Class TestTaskRunnerWakeupService.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution
 */
class TestTaskRunnerWakeupService extends TaskRunnerWakeupService
{
    private array $callHistory = [];

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : [];
    }

    public function resetCallHistory()
    {
        $this->callHistory = [];
    }

    public function wakeup()
    {
        $this->callHistory['wakeup'][] = [];
    }
}
