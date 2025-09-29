<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\Sdk;

use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface as IntegrationMetadataProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Sdk\Authentication\Authenticator;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\Connection;
use CAWL\OnlinePayments\Sdk\Communication\MetadataProvider;
use CAWL\OnlinePayments\Sdk\Communication\MetadataProviderInterface;
use CAWL\OnlinePayments\Sdk\Communicator;
use CAWL\OnlinePayments\Sdk\CommunicatorConfiguration;
/**
 * Class MetricsProvidingCommunicator.
 *
 * @package OnlinePayments\Core\Bootstrap\Sdk
 */
class MetricsProvidingCommunicator extends Communicator
{
    private ActiveBrandProviderInterface $activeBrandProvider;
    private IntegrationMetadataProviderInterface $integrationMetadataProvider;
    private StoreContext $storeContext;
    public function __construct(CommunicatorConfiguration $communicatorConfiguration, Authenticator $authenticator, Connection $connection, ActiveBrandProviderInterface $activeBrandProvider, IntegrationMetadataProviderInterface $integrationMetadataProvider, StoreContext $storeContext, ?MetadataProviderInterface $metadataProvider = null)
    {
        $this->activeBrandProvider = $activeBrandProvider;
        $this->integrationMetadataProvider = $integrationMetadataProvider;
        $this->storeContext = $storeContext;
        parent::__construct($communicatorConfiguration, $authenticator, $connection, $metadataProvider);
    }
    protected function getRequestHeaders($httpMethod, $relativeUriPathWithRequestParameters, $contentType = null, $clientMetaInfo = '', ?CallContext $callContext = null)
    {
        $headers = parent::getRequestHeaders($httpMethod, $relativeUriPathWithRequestParameters, $contentType, $clientMetaInfo, $callContext);
        $integrationMetadata = $this->integrationMetadataProvider->getMetadata();
        $headers['platforminfo'] = \base64_encode(\json_encode(['sdk"' => ['sdk' => 'php', 'version' => MetadataProvider::SDK_VERSION], 'environment' => ['runtimeVersion' => !empty($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '', 'timestamp' => self::getRfc161Date()], 'platform' => ['name' => $integrationMetadata->getPlatformName(), 'version' => $integrationMetadata->getPlatformVersion(), 'variant' => $integrationMetadata->getPlatformVariant()], 'plugin' => ['version' => $integrationMetadata->getPluginVersion(), 'brand' => $this->activeBrandProvider->getActiveBrand()->getName(), 'metadata' => ['origin' => $this->storeContext->getOrigin(), 'store' => ['id' => $this->storeContext->getStoreId(), 'url' => $integrationMetadata->getStoreUrl()]]]], \JSON_UNESCAPED_SLASHES));
        $this->storeContext->resetOrigin();
        return $headers;
    }
}
