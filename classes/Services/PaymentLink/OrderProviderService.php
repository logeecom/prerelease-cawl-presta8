<?php

namespace OnlinePayments\Classes\Services\PaymentLink;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Address;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\ContactDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\Customer;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\PersonalInformation;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\LineItem;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\LineItemCollection;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Product;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Shipping;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Country;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\TaxableAmount;
use WorldlineOP\PrestaShop\Utils\Tools;

/**
 * Class CartProviderService.
 *
 * @package OnlinePayments\Classes\Services\PaymentLink
 */
class OrderProviderService implements CartProvider
{
    private string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function get(): Cart
    {
        $psOrder = new \Order((int)$this->orderId);

        $customerAddress = new \Address($psOrder->id_address_invoice);
        $shippingAddress = new \Address($psOrder->id_address_invoice);
        $cartCurrency = new \Currency($psOrder->id_currency);

        $cartTotal = $this->getTotalAmount($psOrder->getOrdersTotalPaid(), $cartCurrency);


        return new Cart(
            \Cart::getCartIdByOrderId($psOrder->id),
            $cartTotal,
            $this->convertAmountInEuros((float)$cartTotal->getPriceInCurrencyUnits(), $cartCurrency),
            $this->getCustomer($psOrder, $customerAddress),
            $this->getLineItems($psOrder->getProducts(), $cartCurrency),
            $this->getShipping($psOrder, $shippingAddress, $cartCurrency),
            $this->getDiscountAmount($psOrder->total_discounts, $cartCurrency)
        );
    }

    private function getLineItems(array $products, \Currency $cartCurrency): LineItemCollection
    {
        $lineItems = new LineItemCollection();
        foreach ($products as $product) {
            $lineItems->add(new LineItem(
                new Product(
                    (string)$product['id_product'],
                    (string)$product['product_name'],
                    (string)$product['reference'] ?: $product['unique_id']
                ),
                TaxableAmount::fromAmounts(
                    Amount::fromFloat(
                        (float)$product['unit_price_tax_excl'],
                        Currency::fromIsoCode($cartCurrency->iso_code)
                    ),
                    Amount::fromFloat(
                        (float)$product['unit_price_tax_incl'],
                        Currency::fromIsoCode($cartCurrency->iso_code)
                    )
                ),
                (int)$product['product_quantity']
            ));
        }

        return $lineItems;
    }
    private function getCustomer(\Order $psOrder, \Address $customerAddress): Customer
    {
        $cartIsoLang = \Language::getIsoById($psOrder->id_lang);

        $psCustomer = $psOrder->getCustomer();

        return new Customer(
            new ContactDetails($psCustomer->email),
            $this->getAddress($customerAddress),
            $psCustomer->id,
            $psCustomer->isGuest(),
            \Language::getLocaleByIso($cartIsoLang)
        );
    }

    private function getShipping(\Order $psOrder, \Address $shippingAddress, \Currency $currency): Shipping
    {
        return new Shipping(
            TaxableAmount::fromAmounts(
                Amount::fromFloat(
                    $psOrder->total_shipping_tax_excl,
                    Currency::fromIsoCode($currency->iso_code)
                ),
                Amount::fromFloat(
                    $psOrder->total_shipping_tax_incl,
                    Currency::fromIsoCode($currency->iso_code)
                )
            ),
            $this->getAddress($shippingAddress),
            new ContactDetails(
                $psOrder->getCustomer()->email,
                !empty($shippingAddress->phone) ? $shippingAddress->phone : (string)$shippingAddress->phone_mobile
            )
        );
    }

    private function getTotalAmount(float $orderTotal, \Currency $cartCurrency): Amount
    {
        return Amount::fromFloat(
            $orderTotal,
            Currency::fromIsoCode($cartCurrency->iso_code)
        );
    }

    private function getDiscountAmount(float $discountAmount, \Currency $cartCurrency): Amount
    {
        return Amount::fromFloat(
            $discountAmount,
            Currency::fromIsoCode($cartCurrency->iso_code)
        );
    }

    private function getAddress(\Address $address): Address
    {
        return new Address(
            Country::fromIsoCode(\Country::getIsoById($address->id_country)),
            $address->id_state ? \State::getNameById($address->id_state) : '',
            $address->city,
            $address->postcode,
            $address->address1,
            '',
            new PersonalInformation(
                $address->firstname,
                $address->lastname
            )
        );
    }

    private function convertAmountInEuros(float $amount, \Currency $fromCurrency): ?Amount
    {
        $currencyEUR = Tools::getCurrencyByIsoCode('EUR');
        $cartTotalInEuros = null;
        if (false !== $currencyEUR) {
            $cartTotalInEuros = Amount::fromFloat(
                $amount * (float)$currencyEUR->conversion_rate / $fromCurrency->conversion_rate,
                Currency::fromIsoCode('EUR')
            );
        }

        return $cartTotalInEuros;
    }
}