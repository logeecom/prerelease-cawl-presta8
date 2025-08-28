<?php

namespace OnlinePayments\Core\Bootstrap\Configuration;

use OnlinePayments\Core\Infrastructure\Configuration\Configuration as InfrastructureConfiguration;
use OnlinePayments\Core\Infrastructure\Singleton;

/**
 * Class Configuration
 *
 * @package OnlinePayments\Core\Bootstrap\Configuration
 */
abstract class Configuration extends InfrastructureConfiguration
{
    /**
     * Singleton instance of this class.
     *
     * @var ?Singleton
     */
    protected static ?Singleton $instance = null;

    /**
     * Retrieves integration version.
     *
     * @return string Integration version.
     */
    abstract public function getIntegrationVersion(): string;

    /**
     * Gets the current plugin name
     *
     * @return string
     */
    abstract public function getPluginName(): string;

    /**
     * Gets the current plugin version (e.g. 1.2.5)
     *
     * @return string
     */
    abstract public function getPluginVersion(): string;
}