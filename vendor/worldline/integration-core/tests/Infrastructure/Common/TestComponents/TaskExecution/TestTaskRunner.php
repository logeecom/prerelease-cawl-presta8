<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use OnlinePayments\Core\Infrastructure\TaskExecution\TaskRunner;

/**
 * Class TestTaskRunner.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution
 */
class TestTaskRunner extends TaskRunner
{
    private array $callHistory = [];

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : [];
    }

    public function run()
    {
        $this->callHistory['run'][] = [];
    }

    public function setGuid($guid): void
    {
        $this->callHistory['setGuid'][] = ['guid' => $guid];
        parent::setGuid($guid);
    }
}
