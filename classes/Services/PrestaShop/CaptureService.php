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
        $order = new \Order((int) $transaction['idOrder']);
        if (!\Validate::isLoadedObject($order)) {
            return $this->module->l('Unexpected error occurred during capture.', self::FILE_NAME);
        }
        try {
            $captureResponse = OrderAPI::get()->capture($this->storeId)->handle(new CaptureRequest(PaymentId::parse($transaction['id']), Amount::fromFloat($transaction['amountToCapture'], Currency::fromIsoCode($transaction['currencyCode'])), (string) $order->id_cart));
            if (!$captureResponse->isSuccessful()) {
                return \sprintf($this->module->l('Capture creation failed on %s!', self::FILE_NAME), $this->module->getBrand()->getName());
            }
        } catch (Exception $e) {
            return $this->module->l('Unexpected error occurred during capture.', self::FILE_NAME);
        }
        $capture = $captureResponse->toArray();
        if (!\in_array($capture['statusCode'], \array_merge(StatusCode::CAPTURE_STATUS_CODES, StatusCode::CAPTURE_REQUESTED_STATUS_CODES))) {
            return $this->module->l("Capture of funds failed. Payment is not in Capture or Capture requested status.", self::FILE_NAME);
        }
        return '';
    }
}
