<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk\Merchant\Webhooks;

use Exception;
use CAWL\OnlinePayments\Sdk\ApiResource;
use CAWL\OnlinePayments\Sdk\CallContext;
use CAWL\OnlinePayments\Sdk\Communication\ErrorResponseException;
use CAWL\OnlinePayments\Sdk\Communication\ResponseClassMap;
use CAWL\OnlinePayments\Sdk\Domain\SendTestRequest;
use CAWL\OnlinePayments\Sdk\Domain\ValidateCredentialsRequest;
use CAWL\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Webhooks client.
 * @internal
 */
class WebhooksClient extends ApiResource implements WebhooksClientInterface
{
    /** @var ExceptionFactory|null */
    private $responseExceptionFactory = null;
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function validateWebhookCredentials(ValidateCredentialsRequest $body, ?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ValidateCredentialsResponse';
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/webhooks/validateCredentials'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function sendTestWebhook(SendTestRequest $body, ?CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = 'CAWL\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/webhooks/sendtest'), $this->getClientMetaInfo(), $body, null, $callContext);
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
