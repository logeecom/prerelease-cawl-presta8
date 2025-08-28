<?php

namespace OnlinePayments\Core\Bootstrap\Disconnect\Tasks;

use DateTime;
use Exception;
use OnlinePayments\Core\Bootstrap\DataAccess\Disconnect\DisconnectRepository;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\Serializer\Serializer;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Task;

/**
 * Class DisconnectTask
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Disconnect\Tasks
 */
class DisconnectTask extends Task
{
    private string $storeId;
    private DateTime $dateTime;

    /**
     * @param string $storeId
     * @param DateTime $dateTime
     */
    public function __construct(string $storeId, DateTime $dateTime)
    {
        $this->storeId = $storeId;
        $this->dateTime = $dateTime;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array): DisconnectTask
    {
        return new static(
            $array['storeId'],
            (new DateTime())->setTimestamp($array['date'])
        );
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'storeId' => $this->storeId,
            'date' => $this->dateTime->getTimestamp(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function serialize(): string
    {
        return Serializer::serialize($this->toArray());
    }

    /**
     * @inheritDoc
     */
    public function unserialize(string $serialized): void
    {
        $unserialized = Serializer::unserialize($serialized);
        $this->storeId = $unserialized['storeId'];
        $this->dateTime = (new DateTime())->setTimestamp($unserialized['date']);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function execute(): void
    {
        StoreContext::doWithStore($this->storeId, function () {
            $this->doExecute();
        });
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function doExecute(): void
    {
        $this->deleteShopLogs();
        $this->reportProgress(45);
        $this->deleteWebhookLogs();
        $this->reportProgress(90);
        $this->getDisconnectRepository()->deleteDisconnectTime();
        $this->reportProgress(100);
    }

    protected function deleteShopLogs(): void
    {
        // ToDo add implementation
    }

    protected function deleteWebhookLogs(): void
    {
        // ToDo add implementation
    }

    /**
     * @return DisconnectRepository
     */
    protected function getDisconnectRepository(): DisconnectRepository
    {
        return ServiceRegister::getService(DisconnectRepository::class);
    }
}