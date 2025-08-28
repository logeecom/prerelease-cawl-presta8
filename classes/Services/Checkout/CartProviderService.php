<?php

namespace OnlinePayments\Classes\Services\Checkout;

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
 * @package OnlinePayments\Classes\Services\Checkout
 */
class CartProviderService implements CartProvider
{
    private \Context $context;

    public function __construct(\Context $context)
    {
        $this->context = $context;
    }

    public function get(): Cart
    {
        $customerAddress = new \Address((int)$this->context->cart->id_address_invoice);
        $shippingAddress = new \Address((int)$this->context->cart->id_address_delivery);
        $cartCurrency = new \Currency($this->context->cart->id_currency);

        $cartTotal = $this->getTotalAmount($cartCurrency);

        return new Cart(
            (string)$this->context->cart->id,
            $cartTotal,
            $this->convertAmountInEuros((float)$cartTotal->getPriceInCurrencyUnits(), $cartCurrency),
            $this->getCustomer($customerAddress),
            $this->getLineItems($cartCurrency),
            $this->getShipping($shippingAddress, $cartCurrency),
            $this->getDiscountAmount($cartCurrency)
        );
    }

    private function getLineItems(\Currency $cartCurrency): LineItemCollection
    {
        $lineItems = new LineItemCollection();
        foreach ($this->context->cart->getProducts() as $product) {
            $lineItems->add(new LineItem(
                new Product(
                    (string)$product['id_product'],
                    (string)$product['name'],
                    (string)$product['reference'] ?: $product['unique_id']
                ),
                TaxableAmount::fromAmounts(
                    Amount::fromFloat(
                        (float)$product['price_with_reduction_without_tax'],
                        Currency::fromIsoCode($cartCurrency->iso_code)
                    ),
                    Amount::fromFloat(
                        (float)$product['price_with_reduction'],
                        Currency::fromIsoCode($cartCurrency->iso_code)
                    )
                ),
                (int)$product['quantity']
            ));
        }

        return $lineItems;
    }
    private function getCustomer(\Address $customerAddress): Customer
    {
        $cartIsoLang = \Language::getIsoById($this->context->cart->id_lang);

        return new Customer(
            new ContactDetails($this->context->customer->email),
            $this->getAddress($customerAddress),
            $this->context->customer->id,
            $this->context->customer->isGuest(),
            \Language::getLocaleByIso($cartIsoLang)
        );
    }

    private function getShipping(\Address $shippingAddress, \Currency $currency): Shipping
    {
        return new Shipping(
            TaxableAmount::fromAmounts(
                Amount::fromFloat(
                    $this->context->cart->getOrderTotal(false, \Cart::ONLY_SHIPPING),
                    Currency::fromIsoCode($currency->iso_code)
                ),
                Amount::fromFloat(
                    $this->context->cart->getOrderTotal(true, \Cart::ONLY_SHIPPING),
                    Currency::fromIsoCode($currency->iso_code)
                )
            ),
            $this->getAddress($shippingAddress),
            new ContactDetails(
                $this->context->customer->email,
                !empty($shippingAddress->phone) ? $shippingAddress->phone : (string)$shippingAddress->phone_mobile
            )
        );
    }

    private function getTotalAmount(\Currency $cartCurrency): Amount
    {
        return Amount::fromFloat(
            $this->context->cart->getOrderTotal(),
            Currency::fromIsoCode($cartCurrency->iso_code)
        );
    }

    private function getDiscountAmount(\Currency $cartCurrency): Amount
    {
        return Amount::fromFloat(
            $this->context->cart->getOrderTotal(true, \Cart::ONLY_DISCOUNTS),
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