<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\Services;

use Exception;
use CAWL\OnlinePayments\Sdk\ApiResource;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\ErrorResponseException;
use CAWL\OnlinePayments\Sdk\Communication\ResponseClassMap;
use CAWL\OnlinePayments\Sdk\Domain\CalculateSurchargeRequest;
use CAWL\OnlinePayments\Sdk\Domain\CurrencyConversionRequest;
use CAWL\OnlinePayments\Sdk\Domain\GetIINDetailsRequest;
use CAWL\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Services client.
 * @internal
 */
class ServicesClient extends ApiResource implements ServicesClientInterface
{
    /** @var ExceptionFactory|null */
    private $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function testConnection(?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\TestConnection';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/testconnection'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function getIINDetails(GetIINDetailsRequest $body, ?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\GetIINDetailsResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/getIINdetails'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function getDccRateInquiry(CurrencyConversionRequest $body, ?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\CurrencyConversionResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/dccrate'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function surchargeCalculation(CalculateSurchargeRequest $body, ?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\CalculateSurchargeResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/surchargecalculation'), $this->getClientMetaInfo(), $body, null, $callContext);
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
