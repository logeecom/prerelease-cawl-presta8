<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\Runnable;

class TestAsyncProcessStarter implements AsyncProcessService
{
    /**
     * @var bool
     */
    private $doStartRunner;
    private array $callHistory = [];

    public function __construct($doStartRunner = false)
    {
        $this->doStartRunner = $doStartRunner;
    }

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : [];
    }

    public function start(Runnable $runner)
    {
        $this->callHistory['start'][] = array('runner' => $runner);
        if ($this->doStartRunner) {
            $runner->run();
        }
    }

    /**
     * @param bool $doStartRunner
     */
    public function setDoStartRunner(bool $doStartRunner)
    {
        $this->doStartRunner = $doStartRunner;
    }

    /**
     * @inheritDoc
     */
    public function runProcess($guid)
    {
    }
}
