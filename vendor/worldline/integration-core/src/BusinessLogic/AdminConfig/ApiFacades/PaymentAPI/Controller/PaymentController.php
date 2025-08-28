<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Controller;

use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Request\PaymentMethodRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response\PaymentMethodEnableResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response\PaymentMethodResponse as ApiPaymentMethodResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response\PaymentMethodSaveResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Response\PaymentMethodsResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Payment\PaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidActionTypeException;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidPaymentProductIdException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidRecurrenceTypeException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidSessionTimeoutException;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Exceptions\InvalidSignatureTypeException;

/**
 * Class PaymentController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Controller
 */
class PaymentController
{
    protected PaymentService $paymentService;
    protected ShopPaymentService  $shopPaymentService;

    /**
     * @param PaymentService $paymentService
     * @param ShopPaymentService $shopPaymentService
     */
    public function __construct(PaymentService $paymentService, ShopPaymentService $shopPaymentService)
    {
        $this->paymentService = $paymentService;
        $this->shopPaymentService = $shopPaymentService;
    }


    /**
     * @return PaymentMethodsResponse
     */
    public function list(): PaymentMethodsResponse
    {
        return new PaymentMethodsResponse($this->paymentService->getPaymentMethods());
    }

    /**
     * @param string $paymentProductId
     * @param bool $enabled
     *
     * @return PaymentMethodEnableResponse
     *
     * @throws InvalidPaymentProductIdException
     * @throws InvalidSessionTimeoutException
     */
    public function enable(string $paymentProductId, bool $enabled): PaymentMethodEnableResponse
    {
        $this->paymentService->enablePaymentMethod($paymentProductId, $enabled);
        $this->shopPaymentService->enable($paymentProductId, $enabled);

        return new PaymentMethodEnableResponse();
    }

    /**
     * @param PaymentMethodRequest $paymentMethodRequest
     *
     * @return PaymentMethodSaveResponse
     *
     * @throws InvalidPaymentProductIdException
     * @throws InvalidSessionTimeoutException
     * @throws InvalidActionTypeException
     * @throws InvalidRecurrenceTypeException
     * @throws InvalidSignatureTypeException
     */
    public function save(PaymentMethodRequest $paymentMethodRequest): PaymentMethodSaveResponse
    {
        $method = $paymentMethodRequest->transformToDomainModel();
        $this->paymentService->savePaymentMethod($method);
        $this->shopPaymentService->savePaymentMethod($method);

        return new PaymentMethodSaveResponse();
    }

    /**
     * @param string $paymentProductId
     *
     * @return ApiPaymentMethodResponse
     */
    public function getPaymentMethod(string $paymentProductId): ApiPaymentMethodResponse
    {
        return new ApiPaymentMethodResponse($this->paymentService->getPaymentMethod($paymentProductId));
    }
}
