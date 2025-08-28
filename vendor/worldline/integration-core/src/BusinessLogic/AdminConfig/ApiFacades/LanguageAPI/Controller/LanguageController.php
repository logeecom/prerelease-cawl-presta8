<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\LanguageAPI\Controller;

use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\LanguageAPI\Response\LanguageResponse;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Language\LanguageService;

/**
 * Class LanguageController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\LanguageAPI\Controller
 */
class LanguageController
{
    protected LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    public function getLanguages(): LanguageResponse
    {
        return new LanguageResponse($this->languageService->getEnabledLanguages());
    }
}
