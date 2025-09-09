<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Request\Request;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Exceptions\InvalidCurrencyCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidExemptionTypeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\ExemptionType;
/**
 * Class CardsSettingsRequest
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request
 */
class CardsSettingsRequest extends Request
{
    protected bool $enable3ds;
    protected bool $enforceStrongAuthentication;
    protected bool $enable3dsExemption;
    protected string $exemptionType;
    protected float $amount;
    /**
     * @param bool $enable3ds
     * @param bool $enforceStrongAuthentication
     * @param bool $enable3dsExemption
     * @param string $exemptionType
     * @param float $amount
     */
    public function __construct(bool $enable3ds, bool $enforceStrongAuthentication, bool $enable3dsExemption, string $exemptionType, float $amount)
    {
        $this->enable3ds = $enable3ds;
        $this->enforceStrongAuthentication = $enforceStrongAuthentication;
        $this->enable3dsExemption = $enable3dsExemption;
        $this->exemptionType = $exemptionType;
        $this->amount = $amount;
    }
    /**
     * @inheritDoc
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidExemptionTypeException
     */
    public function transformToDomainModel() : object
    {
        return new CardsSettings($this->enable3ds, $this->enforceStrongAuthentication, $this->enable3dsExemption, ExemptionType::fromState($this->exemptionType), Amount::fromFloat($this->amount, Currency::fromIsoCode('EUR')));
    }
}
