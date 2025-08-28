<?php

namespace OnlinePayments\Classes\Services\Domain\Repositories;

use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionRepository as CorePaymentTransactionRepository;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;

/**
 * Class PaymentTransactionRepository.
 *
 * @package OnlinePayments\Classes\Services\Domain\Repositories
 */
class PaymentTransactionRepository extends CorePaymentTransactionRepository
{
    public function lockOrderCreation(?PaymentId $paymentId): bool
    {
        \Db::getInstance()->disableCache();

        return parent::lockOrderCreation($paymentId);
    }
}