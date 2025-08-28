<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Webhook\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\Webhook\WebhookData;

/**
 * Interface WebhookTransformerInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Webhook\Transformers
 */
interface WebhookTransformerInterface
{
    /**
     * @param string $webhookBody
     * @param array $requestHeaders
     *
     * @return WebhookData
     */
    public function transform(string $webhookBody, array $requestHeaders): WebhookData;
}
