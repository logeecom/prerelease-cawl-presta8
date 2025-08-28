<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility;

use OnlinePayments\Core\Infrastructure\Utility\GuidProvider;

/**
 * Class TestGuidProvider.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility
 */
class TestGuidProvider extends GuidProvider
{
    /**
     * @var string
     */
    private string $guid = '';

    /**
     * @return string
     */
    public function generateGuid(): string
    {
        if (empty($this->guid)) {
            return parent::generateGuid();
        }

        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }
}
