<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Address;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\ContactDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\Customer;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Country;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;

/**
 * Class MockCartProvider.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks
 */
class MockCartProvider implements CartProvider
{
    public function get(): Cart
    {
        return new Cart(
            'testOrder132',
            Amount::fromFloat(123.45, Currency::fromIsoCode('EUR')),
            Amount::fromFloat(123.45, Currency::fromIsoCode('EUR')),
            new Customer(
                new ContactDetails('test@example.com'),
                new Address(
                    Country::fromIsoCode('DE'),
                    '',
                    'Berlin',
                    '10171',
                    'Test street',
                    '123A'
                )
            )
        );
    }
}