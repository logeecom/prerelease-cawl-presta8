<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Versions\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use OnlinePayments\Core\BusinessLogic\Domain\Version\VersionInfo;

/**
 * Class MockVersionService
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Versions\Mocks
 */
class MockVersionService implements VersionService
{
    /**
     * @inheritDoc
     */
    public function getVersionInfo(): VersionInfo
    {
        return new VersionInfo('1.0.0', '2.0.0');
    }
}
