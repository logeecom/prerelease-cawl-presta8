<?php

namespace OnlinePayments\Tests;

use OnlinePayments\Classes\Services\Integration\ConfigService;
use OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigurationServiceTest
 *
 * @package OnlinePayments\Tests
 */
class ConfigurationServiceTest extends TestCase
{
    /** @var Configuration */
    public $configService;

    public function setUp(): void
    {
        ConfigService::resetInstance();
        $this->configService = ConfigService::getInstance();
        $me = $this;
        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($me) {
                return $me->configService;
            }
        );
    }

    public function testCorrectVersion(): void
    {
        $this->assertEquals(_PS_VERSION_, $this->configService->getIntegrationVersion());
        $this->assertEquals('PrestaShop', $this->configService->getIntegrationName());
    }
}
