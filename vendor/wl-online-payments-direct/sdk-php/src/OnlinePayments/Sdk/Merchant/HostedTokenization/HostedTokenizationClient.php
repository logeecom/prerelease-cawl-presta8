<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\HostedTokenization;

use Exception;
use CAWL\OnlinePayments\Sdk\ApiResource;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\ErrorResponseException;
use CAWL\OnlinePayments\Sdk\Communication\ResponseClassMap;
use CAWL\OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use CAWL\OnlinePayments\Sdk\ExceptionFactory;
/**
 * HostedTokenization client.
 * @internal
 */
class HostedTokenizationClient extends ApiResource implements HostedTokenizationClientInterface
{
    /** @var ExceptionFactory|null */
    private $responseExceptionFactory = null;
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function createHostedTokenization(CreateHostedTokenizationRequest $body, ?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\CreateHostedTokenizationResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedtokenizations'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getHostedTokenization($hostedTokenizationId, ?CallContext $callContext = null)
    {
        $this->context['hostedTokenizationId'] = $hostedTokenizationId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\GetHostedTokenizationResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedtokenizations/{hostedTokenizationId}'), $this->getClientMetaInfo(), null, $callContext);
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
