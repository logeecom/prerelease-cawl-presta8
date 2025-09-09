<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\Time;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use CAWL\OnlinePayments\Core\Infrastructure\Utility\TimeProvider as InfrastructureTimeProvider;
/**
 * Interface TimeProvider.
 *
 * @package OnlinePayments\Core\Bootstrap\Time
 * @internal
 */
class TimeProvider implements TimeProviderInterface
{
    private InfrastructureTimeProvider $timeProvider;
    public function __construct(InfrastructureTimeProvider $timeProvider)
    {
        $this->timeProvider = $timeProvider;
    }
    public function getCurrentLocalTime() : \DateTime
    {
        return $this->timeProvider->getCurrentLocalTime();
    }
    public function getDateTime(int $timestamp) : \DateTime
    {
        return $this->timeProvider->getDateTime($timestamp);
    }
    public function getMicroTimestamp() : float
    {
        return $this->timeProvider->getMicroTimestamp();
    }
    public function sleep(int $sleepTime) : void
    {
        $this->timeProvider->sleep($sleepTime);
    }
}
