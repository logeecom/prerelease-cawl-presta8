<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
/**
 * Class GeneralSettingsResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings
 * @internal
 */
class GeneralSettingsResponse
{
    protected ConnectionDetails $connectionDetails;
    protected CardsSettings $cardsSettings;
    protected PaymentSettings $paymentSettings;
    protected LogSettings $logSettings;
    protected PayByLinkSettings $payByLinkSettings;
    /**
     * @param ConnectionDetails $connectionDetails
     * @param CardsSettings $cardsSettings
     * @param PaymentSettings $paymentSettings
     * @param LogSettings $logSettings
     * @param PayByLinkSettings $payByLinkSettings
     */
    public function __construct(ConnectionDetails $connectionDetails, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, LogSettings $logSettings, PayByLinkSettings $payByLinkSettings)
    {
        $this->connectionDetails = $connectionDetails;
        $this->cardsSettings = $cardsSettings;
        $this->paymentSettings = $paymentSettings;
        $this->logSettings = $logSettings;
        $this->payByLinkSettings = $payByLinkSettings;
    }
    /**
     * @return ConnectionDetails
     */
    public function getConnectionDetails() : ConnectionDetails
    {
        return $this->connectionDetails;
    }
    /**
     * @return CardsSettings
     */
    public function getCardsSettings() : CardsSettings
    {
        return $this->cardsSettings;
    }
    /**
     * @return PaymentSettings
     */
    public function getPaymentSettings() : PaymentSettings
    {
        return $this->paymentSettings;
    }
    /**
     * @return LogSettings
     */
    public function getLogSettings() : LogSettings
    {
        return $this->logSettings;
    }
    /**
     * @return PayByLinkSettings
     */
    public function getPayByLinkSettings() : PayByLinkSettings
    {
        return $this->payByLinkSettings;
    }
}
