<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection;

use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies\ConnectionProxyInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionDetailsException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
/**
 * Class ConnectionService
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection
 */
class ConnectionService
{
    protected ConnectionConfigRepositoryInterface $connectionConfigRepository;
    protected ConnectionProxyInterface $proxy;
    /**
     * @param ConnectionConfigRepositoryInterface $connectionConfigRepository
     * @param ConnectionProxyInterface $proxy
     */
    public function __construct(ConnectionConfigRepositoryInterface $connectionConfigRepository, ConnectionProxyInterface $proxy)
    {
        $this->connectionConfigRepository = $connectionConfigRepository;
        $this->proxy = $proxy;
    }
    /**
     * Saves connection config.
     *
     * @param ConnectionDetails $connectionDetails
     *
     * @return void
     *
     * @throws InvalidConnectionDetailsException
     */
    public function connect(ConnectionDetails $connectionDetails) : void
    {
        $this->validateConnectionDetails($connectionDetails);
        $this->connectionConfigRepository->saveConnection($connectionDetails);
    }
    /**
     * Check if user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn() : bool
    {
        $connectionDetails = $this->getConnectionConfig();
        try {
            if ($connectionDetails) {
                $this->validateConnectionDetails($connectionDetails);
                return \true;
            }
        } catch (InvalidConnectionDetailsException $e) {
            // intentionally left empty
        }
        return \false;
    }
    /**
     * Retrieves saved connection config.
     *
     * @return ConnectionDetails|null
     */
    public function getConnectionConfig() : ?ConnectionDetails
    {
        return $this->connectionConfigRepository->getConnection();
    }
    /**
     * @param ConnectionDetails $connectionDetails
     *
     * @return void
     *
     * @throws InvalidConnectionDetailsException
     */
    protected function validateConnectionDetails(ConnectionDetails $connectionDetails) : void
    {
        $this->validate($connectionDetails);
    }
    /**
     * @throws InvalidConnectionDetailsException
     */
    protected function validate(ConnectionDetails $connectionDetails) : void
    {
        $isValid = $this->proxy->isConnectionValid($connectionDetails);
        if (!$isValid) {
            throw new InvalidConnectionDetailsException(new TranslatableLabel('Invalid connection details.', 'connection.apiValidationFailed'));
        }
    }
}
