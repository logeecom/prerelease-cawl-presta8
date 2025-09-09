<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPayByLinkExpirationTimeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
/**
 * Class PayByLinkExpirationTime
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings
 */
class PayByLinkExpirationTime
{
    protected int $days;
    private function __construct(int $days)
    {
        $this->days = $days;
    }
    /**
     * @param int $days
     *
     * @return PayByLinkExpirationTime
     *
     * @throws InvalidPayByLinkExpirationTimeException
     */
    public static function create(int $days) : PayByLinkExpirationTime
    {
        if ($days < 0 || $days > 180) {
            throw new InvalidPayByLinkExpirationTimeException(new TranslatableLabel('Invalid logging records lifetime.', 'generalSettings.logRecordsLifetime.error'));
        }
        return new self($days);
    }
    public function getDays() : int
    {
        return $this->days;
    }
}
