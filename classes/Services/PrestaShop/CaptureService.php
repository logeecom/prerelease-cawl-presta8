<?php

namespace CAWL\OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
/**
 * Class CaptureService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 * @internal
 */
class CaptureService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'CaptureService';
    /** @var OnlinePaymentsModule */
    private $module;
    private int $storeId;
    /**
     * CaptureService constructor.
     */
    public function __construct(OnlinePaymentsModule $module, int $storeId)
    {
        $this->module = $module;
        $this->storeId = $storeId;
    }
    public function handle(array $transaction) : string
    {
        try {
            $captureResponse = OrderAPI::get()->capture($this->storeId)->handle(new CaptureRequest(PaymentId::parse($transaction['id']), Amount::fromFloat($transaction['amountToCapture'], Currency::fromIsoCode($transaction['currencyCode'])), $transaction['idOrder']));
            if (!$captureResponse->isSuccessful()) {
                $errorMessage = 'Capture creation failed on ' . $this->module->getBrand()->getName() . '!';
                return $this->module->l($errorMessage, self::FILE_NAME);
            }
        } catch (Exception $e) {
            return $this->module->l('Unexpected error occurred during refund.', self::FILE_NAME);
        }
        $capture = $captureResponse->toArray();
        if (!\in_array($capture['statusCode'], \array_merge(StatusCode::CAPTURE_STATUS_CODES, StatusCode::CAPTURE_REQUESTED_STATUS_CODES))) {
            return $this->module->l("Capture of funds failed. Payment is not in Capture or Capture requested status.", self::FILE_NAME);
        }
        return '';
    }
}
