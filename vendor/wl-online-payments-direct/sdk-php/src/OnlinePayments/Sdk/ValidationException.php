<?php

/*
 * This file was automatically generated.
 */
namespace CAWL\OnlinePayments\Sdk;

use CAWL\OnlinePayments\Sdk\Domain\DataObject;
/**
 * Class ValidationException
 *
 * @package OnlinePayments\Sdk
 * @internal
 */
class ValidationException extends ResponseException
{
    /**
     * @param int $httpStatusCode
     * @param DataObject $response
     * @param string $message
     */
    public function __construct($httpStatusCode, DataObject $response, $message = null)
    {
        if (\is_null($message)) {
            $message = 'the payment platform returned an incorrect request error response';
        }
        parent::__construct($httpStatusCode, $response, $message);
    }
}
