<?php

namespace CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Events;

use CAWL\OnlinePayments\Core\Infrastructure\Utility\Events\EventBus;
/**
 * Class QueueItemStateTransitionEventBus
 *
 * @package OnlinePayments\Core\Infrastructure\TaskExecution\Events
 * @internal
 */
class QueueItemStateTransitionEventBus extends EventBus
{
    const CLASS_NAME = __CLASS__;
    protected static $instance;
}
