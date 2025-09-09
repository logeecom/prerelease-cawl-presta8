<?php

namespace CAWL\OnlinePayments\Sdk\Communication;

/**
 * Interface MetadataProviderInterface
 *
 * @package OnlinePayments\Sdk\Communication
 * @internal
 */
interface MetadataProviderInterface
{
    /**
     * @return string
     */
    function getServerMetaInfoValue();
}
