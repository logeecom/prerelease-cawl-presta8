<?php

namespace OnlinePayments\Core\Infrastructure\AutoTest;

use OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Logger\LogData;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\Singleton;

/**
 * Class AutoTestLogger.
 *
 * @package OnlinePayments\Core\Infrastructure\AutoConfiguration
 */
class AutoTestLogger extends Singleton implements ShopLoggerAdapter
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static ?Singleton $instance = null;

    /**
     * Logs a message in system.
     *
     * @param LogData $data Data to log.
     *
     * @throws RepositoryNotRegisteredException
     */
    public function logMessage(LogData $data): void
    {
        $repo = RepositoryRegistry::getRepository(LogData::CLASS_NAME);
        $repo->save($data);
    }

    /**
     * Gets all log entities.
     *
     * @return LogData[] An array of the LogData entities, if any.
     *
     * @throws RepositoryNotRegisteredException
     */
    public function getLogs(): array
    {
        return RepositoryRegistry::getRepository(LogData::CLASS_NAME)->select();
    }

    /**
     * Transforms logs to the plain array.
     *
     * @return array An array of logs.
     *
     * @throws RepositoryNotRegisteredException
     */
    public function getLogsArray(): array
    {
        $result = array();
        foreach ($this->getLogs() as $log) {
            $result[] = $log->toArray();
        }

        return $result;
    }
}
