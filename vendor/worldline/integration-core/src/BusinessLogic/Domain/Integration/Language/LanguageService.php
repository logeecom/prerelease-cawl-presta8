<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Language;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Language\Language;
/**
 * Interface LanguageService
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Integration\Language
 * @internal
 */
interface LanguageService
{
    /**
     * @return Language[]
     */
    public function getEnabledLanguages() : array;
}
