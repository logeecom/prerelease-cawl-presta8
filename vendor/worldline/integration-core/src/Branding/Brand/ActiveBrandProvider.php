<?php

namespace OnlinePayments\Core\Branding\Brand;

use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

/**
 * Class ActiveBrandProvide.
 *
 * @package OnlinePayments\Core\Branding\Brand
 */
class ActiveBrandProvider implements ActiveBrandProviderInterface
{
    /**
     * @var callable
     */
    private $activeBrandResolver;

    public function __construct(callable $activeBrandResolver)
    {
        $this->activeBrandResolver = $activeBrandResolver;
    }

    public function getActiveBrand(): BrandConfig
    {
        /** @var string $activeBrand */
        $activeBrand = call_user_func($this->activeBrandResolver);
        $brandConfigPath = dirname(__DIR__) . "/$activeBrand/brand.json";

        if (file_exists($brandConfigPath)) {
            $brandConfig = json_decode(file_get_contents($brandConfigPath), true);

            return new BrandConfig(
                $brandConfig['code'],
                $brandConfig['name'],
                $brandConfig['liveApiEndpoint'],
                $brandConfig['testApiEndpoint'],
                $brandConfig['liveUrl'],
                $brandConfig['testUrl'],
                $brandConfig['paymentMethodName']
            );
        }

        throw new \InvalidArgumentException("Brand ($activeBrand) configuration not found!");
    }

    public function getApiUrl(): string
    {
        /** @var ActiveConnectionProvider $activeConnectionProvider */
        $activeConnectionProvider = ServiceRegister::getService(ActiveConnectionProvider::class);

        return $activeConnectionProvider->get()->getMode()->equals(ConnectionMode::live()) ?
            $this->getActiveBrand()->getLiveUrl() : $this->getActiveBrand()->getTestUrl();
    }

    public function getTransactionUrl(): string
    {
        return $this->getApiUrl() . '/transactions/online/';
    }
}