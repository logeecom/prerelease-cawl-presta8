<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use OnlinePayments\Core\Infrastructure\Serializer\Interfaces\Serializable;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use OnlinePayments\Core\Infrastructure\TaskExecution\Task;

/**
 * Class AbortTask.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution
 */
class AbortTask extends Task
{
    public function execute()
    {
        throw new AbortTaskExecutionException('Abort mission!');
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array): Serializable
    {
        return new static();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array();
    }
}
