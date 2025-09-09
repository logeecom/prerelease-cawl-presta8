<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\AutomaticCapture;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAttemptsNumber;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Configuration\IndexMap;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Entity;
/**
 * Class PaymentSettingsConfigEntity
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings
 * @internal
 */
class PaymentSettingsConfigEntity extends Entity
{
    public const CLASS_NAME = __CLASS__;
    protected string $storeId;
    protected string $mode;
    protected PaymentSettings $paymentSettings;
    /**
     * @inheritDoc
     */
    public function getConfig() : EntityConfiguration
    {
        $indexMap = new IndexMap();
        $indexMap->addStringIndex('storeId');
        $indexMap->addStringIndex('mode');
        return new EntityConfiguration($indexMap, 'PaymentSettingsEntity');
    }
    public function inflate(array $data) : void
    {
        parent::inflate($data);
        $this->storeId = $data['storeId'];
        $this->mode = $data['mode'];
        $paymentSettingsData = $data['paymentSettings'];
        $this->paymentSettings = new PaymentSettings(PaymentAction::fromState($paymentSettingsData['paymentAction']), AutomaticCapture::create($paymentSettingsData['automaticCapture']), $paymentSettingsData['paymentAttemptsNumber'] ? PaymentAttemptsNumber::create($paymentSettingsData['paymentAttemptsNumber']) : null, $paymentSettingsData['applySurcharge'], $paymentSettingsData['paymentCapturedStatus'], $paymentSettingsData['paymentErrorStatus'], $paymentSettingsData['paymentPendingStatus'], $paymentSettingsData['paymentAuthorizedStatus'], $paymentSettingsData['paymentCancelledStatus'], $paymentSettingsData['paymentRefundedStatus']);
    }
    public function toArray() : array
    {
        $data = parent::toArray();
        $data['storeId'] = $this->storeId;
        $data['mode'] = $this->mode;
        $data['paymentSettings'] = ['paymentAction' => $this->paymentSettings->getPaymentAction()->getType(), 'automaticCapture' => $this->paymentSettings->getAutomaticCapture()->getValue(), 'paymentAttemptsNumber' => $this->paymentSettings->getPaymentAttemptsNumber()->getPaymentAttemptsNumber(), 'applySurcharge' => $this->paymentSettings->isApplySurcharge(), 'paymentCapturedStatus' => $this->paymentSettings->getPaymentCapturedStatus(), 'paymentErrorStatus' => $this->paymentSettings->getPaymentErrorStatus(), 'paymentPendingStatus' => $this->paymentSettings->getPaymentPendingStatus(), 'paymentAuthorizedStatus' => $this->paymentSettings->getPaymentAuthorizedStatus(), 'paymentCancelledStatus' => $this->paymentSettings->getPaymentCancelledStatus(), 'paymentRefundedStatus' => $this->paymentSettings->getPaymentRefundedStatus()];
        return $data;
    }
    public function getStoreId() : string
    {
        return $this->storeId;
    }
    public function setStoreId(string $storeId) : void
    {
        $this->storeId = $storeId;
    }
    public function getMode() : string
    {
        return $this->mode;
    }
    public function setMode(string $mode) : void
    {
        $this->mode = $mode;
    }
    public function getPaymentSettings() : PaymentSettings
    {
        return $this->paymentSettings;
    }
    public function setPaymentSettings(PaymentSettings $paymentSettings) : void
    {
        $this->paymentSettings = $paymentSettings;
    }
}
