<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\Metadata;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface;
/**
 * Class MetadataProvider.
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class MetadataProvider implements MetadataProviderInterface
{
    private OnlinePaymentsModule $module;
    public function __construct(OnlinePaymentsModule $module)
    {
        $this->module = $module;
    }
    public function getMetadata() : Metadata
    {
        return new Metadata('PrestaShop', \_PS_VERSION_, '', $this->module->version, \Context::getContext()->link->getBaseLink((int) \Configuration::get('PS_SHOP_DEFAULT')));
    }
}
