<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Request\Request;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\AutomaticCapture;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidActionTypeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidAutomaticCaptureValueException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPaymentAttemptsNumberException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAttemptsNumber;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
class PaymentSettingsRequest extends Request
{
    protected string $paymentAction;
    protected int $automaticCapture;
    protected int $paymentAttemptsNumber;
    protected bool $applySurcharge;
    protected string $paymentCapturedStatus;
    protected string $paymentErrorStatus;
    protected string $paymentPendingStatus;
    protected string $paymentAuthorizedStatus;
    protected string $paymentCancelledStatus;
    protected string $paymentRefundedStatus;
    /**
     * @param string $paymentAction
     * @param int $automaticCapture
     * @param int $paymentAttemptsNumber
     * @param bool $applySurcharge
     * @param string $paymentCapturedStatus
     */
    public function __construct(string $paymentAction, int $automaticCapture, int $paymentAttemptsNumber, bool $applySurcharge, string $paymentCapturedStatus, string $paymentErrorStatus, string $paymentPendingStatus, string $paymentAuthorizedStatus, string $paymentCancelledStatus, string $paymentRefundedStatus)
    {
        $this->paymentAction = $paymentAction;
        $this->automaticCapture = $automaticCapture;
        $this->paymentAttemptsNumber = $paymentAttemptsNumber;
        $this->applySurcharge = $applySurcharge;
        $this->paymentCapturedStatus = $paymentCapturedStatus;
        $this->paymentErrorStatus = $paymentErrorStatus;
        $this->paymentPendingStatus = $paymentPendingStatus;
        $this->paymentAuthorizedStatus = $paymentAuthorizedStatus;
        $this->paymentCancelledStatus = $paymentCancelledStatus;
        $this->paymentRefundedStatus = $paymentRefundedStatus;
    }
    /**
     * @inheritDoc
     *
     * @throws InvalidActionTypeException
     * @throws InvalidAutomaticCaptureValueException
     * @throws InvalidPaymentAttemptsNumberException
     */
    public function transformToDomainModel() : object
    {
        return new PaymentSettings(PaymentAction::fromState($this->paymentAction), AutomaticCapture::create($this->automaticCapture), PaymentAttemptsNumber::create($this->paymentAttemptsNumber), $this->applySurcharge, $this->paymentCapturedStatus, $this->paymentErrorStatus, $this->paymentPendingStatus, $this->paymentAuthorizedStatus, $this->paymentCancelledStatus, $this->paymentRefundedStatus);
    }
}
