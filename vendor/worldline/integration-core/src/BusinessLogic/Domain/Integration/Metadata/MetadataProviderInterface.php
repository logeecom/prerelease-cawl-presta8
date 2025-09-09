<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata;

/**
 * Interface MetadataProviderInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata
 * @internal
 */
interface MetadataProviderInterface
{
    public function getMetadata() : Metadata;
}
