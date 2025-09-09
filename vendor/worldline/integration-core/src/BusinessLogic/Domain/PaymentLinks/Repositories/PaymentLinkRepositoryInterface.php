<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLink;
/**
 * Interface PaymentLinkRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories
 * @internal
 */
interface PaymentLinkRepositoryInterface
{
    public function save(PaymentLink $paymentLink) : void;
    public function getByMerchantReference(string $reference) : ?PaymentLink;
}
