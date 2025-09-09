<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring;

use DateTime;
use DateTimeInterface;
/**
 * Class MonitoringLog
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Monitoring
 */
class MonitoringLog
{
    private string $orderId;
    private string $paymentNumber;
    /**
     * One of Critical, Error, Warning, Info and Debug.
     *
     * @var string
     */
    private string $logLevel;
    private string $message;
    private ?DateTime $createdAt;
    private string $requestMethod;
    private string $requestEndpoint;
    private string $requestBody;
    private string $statusCode;
    private string $responseBody;
    private string $transactionLink;
    private string $orderLink;
    /**
     * @param string $orderId
     * @param string $paymentNumber
     * @param string $logLevel
     * @param string $message
     * @param DateTime|null $createdAt
     * @param string $requestMethod
     * @param string $requestEndpoint
     * @param string $requestBody
     * @param string $statusCode
     * @param string $responseBody
     * @param string $transactionLink
     * @param string $orderLink
     */
    public function __construct(string $orderId, string $paymentNumber, string $logLevel, string $message, ?DateTime $createdAt, string $requestMethod, string $requestEndpoint, string $requestBody, string $statusCode, string $responseBody, string $transactionLink = '', string $orderLink = '')
    {
        $this->orderId = $orderId;
        $this->paymentNumber = $paymentNumber;
        $this->logLevel = $logLevel;
        $this->message = $message;
        $this->createdAt = $createdAt;
        $this->requestMethod = $requestMethod;
        $this->requestEndpoint = $requestEndpoint;
        $this->requestBody = $requestBody;
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->transactionLink = $transactionLink;
        $this->orderLink = $orderLink;
    }
    public function toArray() : array
    {
        return ['orderId' => $this->orderId, 'paymentNumber' => $this->paymentNumber, 'logLevel' => $this->logLevel, 'message' => $this->message, 'createdAt' => $this->createdAt->format(DateTimeInterface::ATOM), 'requestMethod' => $this->requestMethod, 'requestEndpoint' => $this->requestEndpoint, 'requestBody' => $this->requestBody, 'statusCode' => $this->statusCode, 'responseBody' => $this->responseBody, 'transactionLink' => $this->transactionLink, 'orderLink' => $this->orderLink];
    }
    public function getOrderId() : string
    {
        return $this->orderId;
    }
    public function getPaymentNumber() : string
    {
        return $this->paymentNumber;
    }
    public function getLogLevel() : string
    {
        return $this->logLevel;
    }
    public function getMessage() : string
    {
        return $this->message;
    }
    public function getCreatedAt() : ?DateTime
    {
        return $this->createdAt;
    }
    public function getRequestMethod() : string
    {
        return $this->requestMethod;
    }
    public function getRequestEndpoint() : string
    {
        return $this->requestEndpoint;
    }
    public function getRequestBody() : string
    {
        return $this->requestBody;
    }
    public function getStatusCode() : string
    {
        return $this->statusCode;
    }
    public function getResponseBody() : string
    {
        return $this->responseBody;
    }
    public function getTransactionLink() : string
    {
        return $this->transactionLink;
    }
    public function setTransactionLink(string $transactionLink) : void
    {
        $this->transactionLink = $transactionLink;
    }
    public function getOrderLink() : string
    {
        return $this->orderLink;
    }
    public function setOrderLink(string $orderLink) : void
    {
        $this->orderLink = $orderLink;
    }
}
