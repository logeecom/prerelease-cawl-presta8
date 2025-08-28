<?php

namespace OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;

/**
 * Class CaptureService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 */
class CaptureService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'CaptureService';

    private $module;
    private int $storeId;

    /**
     * CaptureService constructor.
     */
    public function __construct(string $moduleName, int $storeId)
    {
        $this->module = \Module::getInstanceByName($moduleName);
        $this->storeId = $storeId;
    }

    public function handle(array $transaction): string
    {
        try {
            $captureResponse = OrderAPI::get()->capture($this->storeId)->handle(new CaptureRequest(
                PaymentId::parse($transaction['id']),
                Amount::fromFloat(
                    $transaction['amountToCapture'],
                    Currency::fromIsoCode($transaction['currencyCode'])
                ),
                $transaction['idOrder']
            ));

            if (!$captureResponse->isSuccessful()) {
                $errorMessage = 'Capture creation failed!';
                if ($captureResponse->toArray() && isset($captureResponse->toArray()['errorMessage'])) {
                    $errorMessage .= ' Reason: ' . $captureResponse->toArray()['errorMessage'];
                }

                return $this->module->l($errorMessage, self::FILE_NAME);
            }
        } catch (Exception $e) {
            return $this->module->l($e->getMessage(), self::FILE_NAME);
        }

        $capture = $captureResponse->toArray();
        if (!in_array($capture['statusCode'], array_merge(
            StatusCode::CAPTURE_STATUS_CODES, StatusCode::CAPTURE_REQUESTED_STATUS_CODES))) {
            return $this->module->l("Capture of funds failed with status {$capture['status']}", self::FILE_NAME);
        }

        return '';
    }
}