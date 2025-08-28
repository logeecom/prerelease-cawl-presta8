<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Versions;

use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;

/**
 * Class VersionsApiTest
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Versions
 */
class VersionsApiTest extends BaseTestCase
{
    public function testGetVersionInfo()
    {
        // act
        $result = AdminAPI::get()->version()->getVersionInfo();

        // assert
        self::assertEquals(['installed' => '1.0.0', 'latest' => '2.0.0'], $result->toArray());
    }
}
