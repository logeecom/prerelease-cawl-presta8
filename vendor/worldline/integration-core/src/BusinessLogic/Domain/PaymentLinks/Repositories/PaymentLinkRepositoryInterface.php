<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLink;

/**
 * Interface PaymentLinkRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories
 */
interface PaymentLinkRepositoryInterface
{
    public function save(PaymentLink $paymentLink): void;

    public function getByMerchantReference(string $reference): ?PaymentLink;
}