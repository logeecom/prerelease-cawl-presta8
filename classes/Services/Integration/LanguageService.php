<?php

namespace OnlinePayments\Classes\Services\Integration;

use Language;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Language\LanguageService as CoreLanguageService;
use OnlinePayments\Core\BusinessLogic\Domain\Language\Exception\InvalidIsoCodeException;
use OnlinePayments\Core\BusinessLogic\Domain\Language\LanguageCode;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

/**
 * Class LanguageService
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class LanguageService implements CoreLanguageService
{

    /**
     * @inheritDoc
     *
     * @throws InvalidIsoCodeException
     */
    public function getEnabledLanguages(): array
    {
        $languages = Language::getLanguages(true, StoreContext::getInstance()->getStoreId());
        /** @var \Module $module */
        $module = ServiceRegister::getService(\Module::class);
        $result = [];

        foreach ($languages as $language) {
            $result[] = new \OnlinePayments\Core\BusinessLogic\Domain\Language\Language(
                strtoupper($language['iso_code']),
                $module->getPathUri() . 'views/assets/images/flags/'  .
                $this->getImageName($language['locale']) . '.svg'
            );
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
    private function getImageName($locale): string
    {
        $iso = LanguageCode::fromIso($locale);

        return strtolower('country-' . $iso->getCountry());
    }
}
