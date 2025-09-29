<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Order\ApiFacades\RefundAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundResponse;
/**
 * Class CreateRefundResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\RefundAPI\Response
 */
class CreateRefundResponse extends Response
{
    private RefundResponse $response;
    /**
     * @param RefundResponse $response
     */
    public function __construct(RefundResponse $response)
    {
        $this->response = $response;
    }
    public function getResponse() : RefundResponse
    {
        return $this->response;
    }
    public function toArray() : array
    {
        return ['status' => $this->response->getStatus(), 'statusCode' => $this->response->getStatusCode()->getCode()];
    }
}
