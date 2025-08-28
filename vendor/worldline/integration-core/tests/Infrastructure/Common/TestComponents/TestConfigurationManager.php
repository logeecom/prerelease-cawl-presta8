<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents;

use OnlinePayments\Core\Infrastructure\Configuration\ConfigurationManager;
use OnlinePayments\Core\Infrastructure\Singleton;

/**
 * Class TestConfigurationManager.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents
 */
class TestConfigurationManager extends ConfigurationManager
{
    protected string $context = 'test';

    /**
     * Singleton instance of this class.
     *
     * @var ?Singleton
     */
    protected static ?Singleton $instance = null;

    public function __construct()
    {
        parent::__construct();

        static::$instance = $this;
    }
}
