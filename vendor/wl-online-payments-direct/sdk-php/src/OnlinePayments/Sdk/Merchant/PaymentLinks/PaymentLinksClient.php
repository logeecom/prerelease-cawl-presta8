<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\PaymentLinks;

use Exception;
use CAWL\OnlinePayments\Sdk\ApiResource;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\ErrorResponseException;
use CAWL\OnlinePayments\Sdk\Communication\ResponseClassMap;
use CAWL\OnlinePayments\Sdk\Domain\CreatePaymentLinkRequest;
use CAWL\OnlinePayments\Sdk\ExceptionFactory;
/**
 * PaymentLinks client.
 * @internal
 */
class PaymentLinksClient extends ApiResource implements PaymentLinksClientInterface
{
    /** @var ExceptionFactory|null */
    private $responseExceptionFactory = null;
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function createPaymentLink(CreatePaymentLinkRequest $body, ?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\PaymentLinkResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/paymentlinks'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getPaymentLinkById($paymentLinkId, ?CallContext $callContext = null)
    {
        $this->context['paymentLinkId'] = $paymentLinkId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\PaymentLinkResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/paymentlinks/{paymentLinkId}'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function cancelPaymentLinkById($paymentLinkId, ?CallContext $callContext = null)
    {
        $this->context['paymentLinkId'] = $paymentLinkId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/paymentlinks/{paymentLinkId}/cancel'), $this->getClientMetaInfo(), null, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /** @return ExceptionFactory */
    private function getResponseExceptionFactory()
    {
        if (\is_null($this->responseExceptionFactory)) {
            $this->responseExceptionFactory = new ExceptionFactory();
        }
        return $this->responseExceptionFactory;
    }
}
