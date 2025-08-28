<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use OnlinePayments\Core\Infrastructure\Serializer\Interfaces\Serializable;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use OnlinePayments\Core\Infrastructure\TaskExecution\Task;

/**
 * Class InvalidTask.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution
 */
class InvalidTask extends Task
{
    public function execute()
    {
    }

    /**
     * @inheritdoc
     * @throws QueueItemDeserializationException
     */
    public function unserialize(string $serialized): void
    {
        throw new QueueItemDeserializationException("Failed to deserialize task.");
    }

    /**
     * @inheritDoc
     * @throws QueueItemDeserializationException
     */
    public static function fromArray(array $array): Serializable
    {
        throw new QueueItemDeserializationException("Failed to deserialize task.");
    }
}
