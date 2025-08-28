<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution;

/**
 * Class BarTask.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution
 */
class BarTask extends FooTask
{
    public function execute()
    {
        parent::execute();

        $this->reportProgress(100);
    }
}
