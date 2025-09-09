<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo;

/**
 * Interface LogoUrlService
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo
 * @internal
 */
interface LogoUrlService
{
    /**
     * @return string
     */
    public function getHostedCheckoutLogoUrl() : string;
    /**
     * @param string $productId
     *
     * @return string
     */
    public function getLogoUrl(string $productId) : string;
}
