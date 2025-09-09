<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Webhook\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Webhook\WebhookData;
/**
 * Interface WebhookTransformerInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Webhook\Transformers
 * @internal
 */
interface WebhookTransformerInterface
{
    /**
     * @param string $webhookBody
     * @param array $requestHeaders
     *
     * @return WebhookData
     */
    public function transform(string $webhookBody, array $requestHeaders) : WebhookData;
}
