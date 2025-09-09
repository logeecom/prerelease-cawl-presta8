<?php

namespace CAWL\OnlinePayments\Classes\Services\Domain\Repositories;

use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionRepository as CorePaymentTransactionRepository;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
/**
 * Class PaymentTransactionRepository.
 *
 * @package OnlinePayments\Classes\Services\Domain\Repositories
 */
class PaymentTransactionRepository extends CorePaymentTransactionRepository
{
    public function lockOrderCreation(?PaymentId $paymentId) : bool
    {
        \Db::getInstance()->disableCache();
        return parent::lockOrderCreation($paymentId);
    }
}
