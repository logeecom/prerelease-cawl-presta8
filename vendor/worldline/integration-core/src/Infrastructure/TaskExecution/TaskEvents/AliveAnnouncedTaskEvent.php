<?php

namespace OnlinePayments\Core\Infrastructure\TaskExecution\TaskEvents;

use OnlinePayments\Core\Infrastructure\Utility\Events\Event;

/**
 * Class AliveAnnouncedTaskEvent.
 *
 * @package OnlinePayments\Core\Infrastructure\TaskExecution\TaskEvents
 */
class AliveAnnouncedTaskEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
}
