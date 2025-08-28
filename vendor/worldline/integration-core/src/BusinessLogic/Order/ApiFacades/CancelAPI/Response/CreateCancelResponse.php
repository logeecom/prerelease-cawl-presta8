<?php

namespace OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CancelAPI\Response;

use OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelResponse;

/**
 * Class CreateCancelResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\CancelAPI\Controller
 */
class CreateCancelResponse extends Response
{
    private CancelResponse $response;

    /**
     * @param CancelResponse $response
     */
    public function __construct(CancelResponse $response)
    {
        $this->response = $response;
    }

    public function getResponse(): CancelResponse
    {
        return $this->response;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->response->getStatus(),
            'statusCode' => $this->response->getStatusCode()->getCode(),
        ];
    }
}