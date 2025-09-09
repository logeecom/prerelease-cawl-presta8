<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPaymentAttemptsNumberException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
/**
 * Class PaymentAttemptsNumber
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings
 * @internal
 */
class PaymentAttemptsNumber
{
    protected int $paymentAttemptsNumber;
    /**
     * @param int $paymentAttemptsNumber
     */
    private function __construct(int $paymentAttemptsNumber)
    {
        $this->paymentAttemptsNumber = $paymentAttemptsNumber;
    }
    /**
     * @param int $paymentAttemptsNumber
     *
     * @return self
     *
     * @throws InvalidPaymentAttemptsNumberException
     */
    public static function create(int $paymentAttemptsNumber) : self
    {
        if ($paymentAttemptsNumber < 0 || $paymentAttemptsNumber > 10) {
            throw new InvalidPaymentAttemptsNumberException(new TranslatableLabel('Invalid payment attempts number ' . $paymentAttemptsNumber, 'generalSettings.paymentAttemptsNumber.error', [(string) $paymentAttemptsNumber]));
        }
        return new self($paymentAttemptsNumber);
    }
    public function getPaymentAttemptsNumber() : int
    {
        return $this->paymentAttemptsNumber;
    }
}
