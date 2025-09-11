<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductService as CorePaymentProductService;
/**
 * Class PaymentProductService
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class PaymentProductService extends CorePaymentProductService
{
    const SUPPORTED_PAYMENT_PRODUCTS = [PaymentProductId::CARDS, PaymentProductId::HOSTED_CHECKOUT, PaymentProductId::ALIPAY, PaymentProductId::APPLE_PAY, PaymentProductId::BANK_TRANSFER, PaymentProductId::BIZUM, PaymentProductId::CHEQUE_VACANCES_CONNECT, PaymentProductId::AMERICAN_EXPRESS, PaymentProductId::BANCONTACT, PaymentProductId::CARTE_BANCAIRE, PaymentProductId::DINERS_CLUB, PaymentProductId::DISCOVER, PaymentProductId::JCB, PaymentProductId::MASTERCARD, PaymentProductId::MAESTRO, PaymentProductId::UPI, PaymentProductId::VISA, PaymentProductId::EPS, PaymentProductId::GOOGLE_PAY, PaymentProductId::IDEAL, PaymentProductId::ILLICADO, PaymentProductId::INTERSOLVE, PaymentProductId::KLARNA, PaymentProductId::MEALVOUCHERS, PaymentProductId::MULTIBANCO, PaymentProductId::ONEY_BRANDED_GIFT_CARD, PaymentProductId::PRZELEWY24, PaymentProductId::PAYPAL, PaymentProductId::POSTFINANCE_PAY, PaymentProductId::TWINT, PaymentProductId::WECHAT_PAY];
    public function getSupportedPaymentMethods() : array
    {
        return self::SUPPORTED_PAYMENT_PRODUCTS;
    }
}
