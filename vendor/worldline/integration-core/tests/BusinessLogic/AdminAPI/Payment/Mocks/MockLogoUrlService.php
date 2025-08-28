<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo\LogoUrlService;

/**
 * Class MockLogoUrlService
 *
 * @package AdminAPI\Payment\Mocks
 */
class MockLogoUrlService implements LogoUrlService
{

    /**
     * @inheritDoc
     */
    public function getHostedCheckoutLogoUrl(): string
    {
        return '';
    }

    public function getLogoUrl(string $productId): string
    {
        return '';
    }
}
