<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\Metadata;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface;

/**
 * Class MockMetadataProvider.
 *
 * @package CheckoutAPI\Mocks
 */
class MockMetadataProvider implements MetadataProviderInterface
{

    public function getMetadata(): Metadata
    {
        return new Metadata(
            'test',
            '1.0.0',
            '',
            '1.2.3',
            'test.example.com'
        );
    }
}