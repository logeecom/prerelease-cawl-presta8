<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use Language;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Language\LanguageService as CoreLanguageService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Language\Exception\InvalidIsoCodeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Language\LanguageCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class LanguageService
 *
 * @package OnlinePayments\Classes\Services\Integration
 * @internal
 */
class LanguageService implements CoreLanguageService
{
    /**
     * @inheritDoc
     *
     * @throws InvalidIsoCodeException
     */
    public function getEnabledLanguages() : array
    {
        $languages = Language::getLanguages(\true, StoreContext::getInstance()->getStoreId());
        /** @var \Module $module */
        $module = ServiceRegister::getService(\Module::class);
        $result = [];
        foreach ($languages as $language) {
            $result[] = new \CAWL\OnlinePayments\Core\BusinessLogic\Domain\Language\Language(\strtoupper($language['iso_code']), $module->getPathUri() . 'views/assets/images/flags/' . $this->getImageName($language['locale']) . '.svg');
        }
        return $result;
    }
    /**
     * @param $locale
     *
     * @return string
     *
     * @throws InvalidIsoCodeException
     */
    private function getImageName($locale) : string
    {
        $iso = LanguageCode::fromIso($locale);
        return \strtolower('country-' . $iso->getCountry());
    }
}
