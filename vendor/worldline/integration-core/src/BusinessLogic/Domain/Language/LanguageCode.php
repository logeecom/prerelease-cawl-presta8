<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Language;

use OnlinePayments\Core\BusinessLogic\Domain\Language\Exception\InvalidIsoCodeException;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;

/**
 * Class LanguageCode
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Language
 */
class LanguageCode
{
    private string $language;
    private string $country;

    /**
     * @param string $language
     * @param string $country
     */
    private function __construct(string $language, string $country)
    {
        $this->language = $language;
        $this->country = $country;
    }

    /**
     * @param string $isoCode
     *
     * @return LanguageCode
     *
     * @throws InvalidIsoCodeException
     */
    public static function fromIso(string $isoCode): LanguageCode
    {
        $parts = explode('_', $isoCode);
        if (isset($parts[1])) {
            return new LanguageCode($parts[0], $parts[1]);
        }

        $parts = explode('-', $isoCode);

        if (isset($parts[1])) {
            return new LanguageCode($parts[0], $parts[1]);
        }

        throw new InvalidIsoCodeException(
            new TranslatableLabel(
                'Invalid ISO code',
                'general.error.invalidIsoCode',
            )
        );
    }

    /**
     * @return string
     */
    public function getFormattedWithDash(): string
    {
        return strtolower($this->language) . '-' . strtoupper($this->country);
    }

    /**
     * @return string
     */
    public function getFormattedWithUnderscore(): string
    {
        return strtolower($this->language) . '_' . strtoupper($this->country);
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }
}
