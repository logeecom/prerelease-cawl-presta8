<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\Configuration;

use CAWL\OnlinePayments\Core\Infrastructure\Configuration\Configuration as InfrastructureConfiguration;
use CAWL\OnlinePayments\Core\Infrastructure\Singleton;
/**
 * Class Configuration
 *
 * @package OnlinePayments\Core\Bootstrap\Configuration
 * @internal
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
    public abstract function getIntegrationVersion() : string;
    /**
     * Gets the current plugin name
     *
     * @return string
     */
    public abstract function getPluginName() : string;
    /**
     * Gets the current plugin version (e.g. 1.2.5)
     *
     * @return string
     */
    public abstract function getPluginVersion() : string;
}
