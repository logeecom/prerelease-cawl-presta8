<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Version;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Version\VersionInfo;
/**
 * Interface VersionService
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Integration\Version
 * @internal
 */
interface VersionService
{
    /**
     * Retrieves plugin current and latest version.
     *
     * @return VersionInfo
     */
    public function getVersionInfo() : VersionInfo;
}
