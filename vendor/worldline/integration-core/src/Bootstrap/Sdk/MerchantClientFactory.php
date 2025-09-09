<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\Sdk;

use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionDetailsException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
use CAWL\OnlinePayments\Sdk\Authentication\V1HmacAuthenticator;
use CAWL\OnlinePayments\Sdk\Client;
use CAWL\OnlinePayments\Sdk\CommunicatorConfiguration;
use CAWL\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
/**
 * Class MerchantClientFactory.
 *
 * @package OnlinePayments\Core\Bootstrap\Sdk
 */
class MerchantClientFactory
{
    private const INTEGRATOR = 'Logeecom';
    private ActiveConnectionProvider $activeConnectionProvider;
    private ActiveBrandProviderInterface $activeBrandProvider;
    private MetadataProviderInterface $metadataProvider;
    public function __construct(ActiveConnectionProvider $activeConnectionProvider, ActiveBrandProviderInterface $activeBrandProvider, MetadataProviderInterface $metadataProvider)
    {
        $this->activeConnectionProvider = $activeConnectionProvider;
        $this->activeBrandProvider = $activeBrandProvider;
        $this->metadataProvider = $metadataProvider;
    }
    public function get(?ConnectionDetails $activeConnection = null) : MerchantClientInterface
    {
        if (null === $activeConnection) {
            $activeConnection = $this->activeConnectionProvider->get();
        }
        if (null === $activeConnection) {
            throw new InvalidConnectionDetailsException(new TranslatableLabel('Connection details are invalid. Missing active credentials.', 'connection.invalidActiveCredentials'));
        }
        $dbLogConnection = new DbLogConnection(new CommunicatorLoggerHelper());
        $dbLogConnection->enableLogging(new ApiLogger());
        $communicatorConfiguration = new CommunicatorConfiguration($activeConnection->getActiveCredentials()->getApiKey(), $activeConnection->getActiveCredentials()->getApiSecret(), $this->getApiEndpoint($activeConnection), self::INTEGRATOR);
        $authenticator = new V1HmacAuthenticator($communicatorConfiguration);
        $communicator = new MetricsProvidingCommunicator($communicatorConfiguration, $authenticator, $dbLogConnection, $this->activeBrandProvider, $this->metadataProvider, StoreContext::getInstance());
        $client = new Client($communicator);
        return $client->merchant($activeConnection->getActiveCredentials()->getPspId());
    }
    private function getApiEndpoint(ConnectionDetails $activeConnection) : string
    {
        $brandConfig = $this->activeBrandProvider->getActiveBrand();
        if ($activeConnection->getMode()->equals(ConnectionMode::live())) {
            return $brandConfig->getLiveApiEndpoint();
        }
        return $brandConfig->getTestApiEndpoint();
    }
}
