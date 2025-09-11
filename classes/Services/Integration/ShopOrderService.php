<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use Db;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\TaxableAmount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\ShopOrderService as ShopOrderServiceInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentDetails;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
use Order;
use OrderDetail;
use PrestaShopDatabaseException;
use PrestaShopException;
/**
 * Class ShopOrderService.
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class ShopOrderService implements ShopOrderServiceInterface
{
    private OnlinePaymentsModule $module;
    public function __construct(OnlinePaymentsModule $module)
    {
        $this->module = $module;
    }
    public function createShopOrder(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState) : void
    {
        if (null !== $this->getIdByCartId($paymentTransaction->getMerchantReference())) {
            $this->updateStatus($paymentTransaction, $paymentDetails, $newState);
            return;
        }
        $cart = new \Cart((int) $paymentTransaction->getMerchantReference());
        if (!\Validate::isLoadedObject($cart)) {
            return;
        }
        $paymentMethodText = $this->module->getBrand()->getName();
        $paymentMethodText .= $paymentTransaction->getPaymentMethod() ? ' [' . $paymentTransaction->getPaymentMethod() . ']' : '';
        $this->module->validateOrder((int) $paymentTransaction->getMerchantReference(), (int) $newState, (float) $paymentDetails->getAmount()->getPriceInCurrencyUnits(), $paymentMethodText, null, ['transaction_id' => $paymentTransaction->getPaymentId()->getTransactionId()], null, \false, $cart->secure_key);
    }
    public function updateStatus(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState) : void
    {
        $orderId = $this->getIdByCartId($paymentTransaction->getMerchantReference());
        if (\false === $orderId) {
            return;
        }
        Order::disableCache();
        Order::cleanHistoryCache();
        $order = new Order($orderId);
        $this->setTimezone($order->id_shop);
        if ((int) $newState === (int) $order->current_state) {
            return;
        }
        Logger::logInfo('Changing order status from ' . $order->current_state . ' to ' . $newState, 'ShopOrderService', ['orderId' => $orderId, 'paymentId' => (string) $paymentTransaction->getPaymentId(), 'merchantReference' => $paymentTransaction->getMerchantReference()]);
        $history = new \OrderHistory();
        $history->id_order = $orderId;
        $history->id_employee = '0';
        $history->changeIdOrderState((int) $newState, $orderId, \true);
        $history->add();
    }
    public function cancelShopOrder(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState) : void
    {
        $this->updateStatus($paymentTransaction, $paymentDetails, $newState);
    }
    public function refundShopOrder(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState) : void
    {
        $orderId = $this->getIdByCartId($paymentTransaction->getMerchantReference());
        if (\false === $orderId) {
            return;
        }
        $order = new Order($orderId);
        $this->setTimezone($order->id_shop);
        $this->refundOnPresta($order, $paymentDetails->getAmounts()->getRefundedAmount());
        $this->updateStatus($paymentTransaction, $paymentDetails, $newState);
    }
    public function refundOnPresta(Order $order, Amount $amountRefundedOnApi) : void
    {
        $currency = new \Currency($order->id_currency);
        $alreadyRefundedProductsAmount = $this->getRefundedProductsAmount($order, $currency);
        $alreadyRefundedShippingAmount = $this->getRefundedShippingAmount($order, $currency);
        $alreadyRefundedTotalAmount = $alreadyRefundedProductsAmount->plus($alreadyRefundedShippingAmount);
        $totalAmountToRefund = $amountRefundedOnApi->minus($alreadyRefundedTotalAmount);
        if ($totalAmountToRefund->getValue() <= 0) {
            return;
        }
        $orderSlipDetails = $this->updateRefundDetailsForOrder($order, $currency, $totalAmountToRefund);
        $this->updateOrderSlips($order, $currency, $totalAmountToRefund, $orderSlipDetails);
    }
    private function updateRefundDetailsForOrder(Order $order, \Currency $currency, Amount $totalAmountToRefund) : array
    {
        $newOrderSlipDetails = [];
        foreach ($order->getOrderDetailList() as $detail) {
            if ($totalAmountToRefund->getValue() <= 0) {
                break;
            }
            $orderDetail = new OrderDetail($detail['id_order_detail']);
            $orderDetailTotal = TaxableAmount::fromAmounts(Amount::fromFloat($orderDetail->total_price_tax_excl, Currency::fromIsoCode($currency->iso_code)), Amount::fromFloat($orderDetail->total_price_tax_incl, Currency::fromIsoCode($currency->iso_code)));
            $orderDetailRefunded = TaxableAmount::fromAmounts(Amount::fromFloat($orderDetail->total_refunded_tax_excl, Currency::fromIsoCode($currency->iso_code)), Amount::fromFloat($orderDetail->total_refunded_tax_incl, Currency::fromIsoCode($currency->iso_code)));
            $refundableAmount = $orderDetailTotal->minus($orderDetailRefunded);
            if ($refundableAmount->getAmountInclTax()->getValue() <= 0) {
                continue;
            }
            $amountToRefund = $refundableAmount;
            if ($refundableAmount->getAmountInclTax()->getValue() >= $totalAmountToRefund->getValue()) {
                $amountToRefund = TaxableAmount::fromAmountInclTaxAndTaxRate($totalAmountToRefund, $amountToRefund->getTaxRate());
            }
            $quantityToAdd = $this->calculateQuantityToAdd($orderDetail, (int) \ceil($amountToRefund->getAmountInclTax()->getPriceInCurrencyUnits() / (float) $orderDetail->unit_price_tax_incl));
            $this->updateOrderDetail($orderDetail, $amountToRefund->getAmountInclTax()->getPriceInCurrencyUnits(), $amountToRefund->getAmountExclTax()->getPriceInCurrencyUnits(), $quantityToAdd);
            $newOrderSlipDetails[] = ['orderDetail' => $orderDetail, 'amountToRefund' => $amountToRefund, 'quantityToAdd' => $quantityToAdd];
            $totalAmountToRefund = $totalAmountToRefund->minus($amountToRefund->getAmountInclTax());
        }
        return $newOrderSlipDetails;
    }
    private function updateOrderSlips(Order $order, \Currency $currency, Amount $totalAmountToRefund, array $newOrderSlipDetails) : void
    {
        $productsAmountToRefund = TaxableAmount::fromAmounts(Amount::fromInt(0, Currency::fromIsoCode($currency->iso_code)), Amount::fromInt(0, Currency::fromIsoCode($currency->iso_code)));
        foreach ($newOrderSlipDetails as $newOrderSlipDetail) {
            $productsAmountToRefund = $productsAmountToRefund->plus($newOrderSlipDetail['amountToRefund']);
        }
        $totalAmountToRefund = $totalAmountToRefund->minus($productsAmountToRefund->getAmountInclTax());
        $shippingAmountToRefund = TaxableAmount::fromAmounts(Amount::fromInt(0, Currency::fromIsoCode($currency->iso_code)), Amount::fromInt(0, Currency::fromIsoCode($currency->iso_code)));
        if ($totalAmountToRefund->getValue() > 0) {
            $alreadyRefundedShippingAmount = $this->getRefundedShippingAmount($order, $currency);
            $shippingTotal = TaxableAmount::fromAmounts(Amount::fromFloat($order->total_shipping_tax_excl, Currency::fromIsoCode($currency->iso_code)), Amount::fromFloat($order->total_shipping_tax_incl, Currency::fromIsoCode($currency->iso_code)));
            $shippingAmountToRefund = $shippingTotal->minus(TaxableAmount::fromAmountInclTaxAndTaxRate($alreadyRefundedShippingAmount, $shippingTotal->getTaxRate()));
        }
        if ($shippingAmountToRefund->getAmountInclTax()->getValue() > $totalAmountToRefund->getValue()) {
            $shippingAmountToRefund = TaxableAmount::fromAmountInclTaxAndTaxRate($totalAmountToRefund, $shippingAmountToRefund->getTaxRate());
        }
        $orderSlip = new \OrderSlip();
        $orderSlip->id_order = $order->id;
        $orderSlip->id_customer = $order->id_customer;
        $orderSlip->conversion_rate = $order->conversion_rate;
        $orderSlip->total_products_tax_incl = $productsAmountToRefund->getAmountInclTax()->getPriceInCurrencyUnits();
        $orderSlip->total_products_tax_excl = $productsAmountToRefund->getAmountExclTax()->getPriceInCurrencyUnits();
        $orderSlip->total_shipping_tax_incl = $shippingAmountToRefund->getAmountInclTax()->getPriceInCurrencyUnits();
        $orderSlip->total_shipping_tax_excl = $shippingAmountToRefund->getAmountExclTax()->getPriceInCurrencyUnits();
        $orderSlip->amount = $productsAmountToRefund->getAmountInclTax()->getPriceInCurrencyUnits();
        $orderSlip->shipping_cost_amount = $shippingAmountToRefund->getAmountInclTax()->getPriceInCurrencyUnits();
        $orderSlip->add();
        foreach ($newOrderSlipDetails as $newOrderSlipDetail) {
            /** @var TaxableAmount $amountToRefund */
            $amountToRefund = $newOrderSlipDetail['amountToRefund'];
            $this->addOrderSlipDetail($orderSlip->id, $newOrderSlipDetail['orderDetail'], $newOrderSlipDetail['quantityToAdd'], $amountToRefund->getAmountInclTax()->getPriceInCurrencyUnits(), $amountToRefund->getAmountExclTax()->getPriceInCurrencyUnits());
        }
    }
    /**
     * @param OrderDetail $orderDetail
     *
     * @param float $amount
     * @param float $amountWithoutTax
     * @param int $quantityRefunded
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function updateOrderDetail(OrderDetail $orderDetail, float $amount, float $amountWithoutTax, int $quantityRefunded) : void
    {
        $orderDetail->total_refunded_tax_incl += $amount;
        $orderDetail->total_refunded_tax_excl += $amountWithoutTax;
        $orderDetail->product_quantity_return += $quantityRefunded;
        $orderDetail->product_quantity_reinjected += $quantityRefunded;
        $orderDetail->total_refunded_tax_incl = \round($orderDetail->total_refunded_tax_incl, 2);
        $orderDetail->total_refunded_tax_excl = \round($orderDetail->total_refunded_tax_excl, 2);
        $orderDetail->update();
    }
    /**
     * @param int $orderSlipId
     * @param OrderDetail $orderDetail
     * @param int $quantity
     * @param float $amount
     * @param float $amountWithoutTax
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     */
    private function addOrderSlipDetail(int $orderSlipId, OrderDetail $orderDetail, int $quantity, float $amount, float $amountWithoutTax) : void
    {
        $quantity = $quantity > 0 ? $quantity : 1;
        Db::getInstance()->insert('order_slip_detail', ['id_order_slip' => (int) $orderSlipId, 'id_order_detail' => $orderDetail->id, 'product_quantity' => $quantity, 'unit_price_tax_excl' => (float) $amountWithoutTax / $quantity, 'unit_price_tax_incl' => (float) $amount / $quantity, 'total_price_tax_incl' => $amount, 'amount_tax_incl' => $amount, 'total_price_tax_excl' => $amountWithoutTax, 'amount_tax_excl' => $amountWithoutTax]);
    }
    private function getRefundedProductsAmount(Order $order, \Currency $currency) : Amount
    {
        $orderCurrency = Currency::fromIsoCode((string) $currency->iso_code);
        $amount = Amount::fromInt(0, $orderCurrency);
        /** @var \OrderSlip[] $orderSlips */
        $orderSlips = $order->getOrderSlipsCollection()->getResults();
        foreach ($orderSlips as $item) {
            $amount = $amount->plus(Amount::fromFloat((float) $item->amount, $orderCurrency));
        }
        return $amount;
    }
    private function getRefundedShippingAmount(Order $order, \Currency $currency) : Amount
    {
        $orderCurrency = Currency::fromIsoCode((string) $currency->iso_code);
        $amount = Amount::fromInt(0, $orderCurrency);
        /** @var \OrderSlip[] $orderSlips */
        $orderSlips = $order->getOrderSlipsCollection()->getResults();
        foreach ($orderSlips as $item) {
            $amount = $amount->plus(Amount::fromFloat((float) $item->shipping_cost_amount, $orderCurrency));
        }
        return $amount;
    }
    private function calculateQuantityToAdd(OrderDetail $orderDetail, int $quantityRefunded) : int
    {
        return $quantityRefunded + (int) $orderDetail->product_quantity_return + (int) $orderDetail->product_quantity_refunded <= (int) $orderDetail->product_quantity ? $quantityRefunded : (int) $orderDetail->product_quantity - (int) $orderDetail->product_quantity_return - (int) $orderDetail->product_quantity_refunded;
    }
    /**
     * @param int $storeId
     *
     * @return void
     */
    private function setTimezone(int $storeId) : void
    {
        $shop = new \Shop($storeId);
        @\date_default_timezone_set(\Configuration::get('PS_TIMEZONE', null, $shop->id_shop_group, $shop->id, \Configuration::get('PS_TIMEZONE')));
    }
    private function getIdByCartId(string $merchantReference) : ?int
    {
        $dbQuery = new \DbQuery();
        $dbQuery->select('o.id_order')->from('orders', 'o')->where('o.id_cart = ' . pSQL((int) $merchantReference));
        $rows = Db::getInstance()->getRow($dbQuery, \false);
        if (empty($rows) || empty($rows['id_order'])) {
            return null;
        }
        return (int) $rows['id_order'];
    }
}
