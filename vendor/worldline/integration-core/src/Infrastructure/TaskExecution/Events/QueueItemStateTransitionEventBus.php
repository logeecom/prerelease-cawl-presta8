<?php

namespace OnlinePayments\Core\Infrastructure\TaskExecution\Events;

use OnlinePayments\Core\Infrastructure\Utility\Events\EventBus;

/**
 * Class QueueItemStateTransitionEventBus
 *
 * @package OnlinePayments\Core\Infrastructure\TaskExecution\Events
 */
class QueueItemStateTransitionEventBus extends EventBus
{
    const CLASS_NAME = __CLASS__;

    protected static $instance;
}
