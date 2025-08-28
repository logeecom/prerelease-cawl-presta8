<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Proxies\Transformers;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CreatePaymentRequestTransformer;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentRequest;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockCartProvider;

/**
 * Class CreatePaymentTransformersTest.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Proxies\Transformers
 */
class CreatePaymentTransformersTest extends BaseTestCase
{
    public function testCreatePaymentRequestTransformerWithoutTax(): void
    {
        $request = CreatePaymentRequestTransformer::transform(
            new PaymentRequest(
                'testHostedTokenizationId',
                new MockCartProvider(),
                'https://shop.test.return.url'
            ),
            new CardsSettings(),
            new PaymentSettings()
        );

        self::assertSame('testHostedTokenizationId', $request->getHostedTokenizationId());
        self::assertNotNull($request->getOrder());
        self::assertSame('EUR', $request->getOrder()->getAmountOfMoney()->getCurrencyCode());
        self::assertSame(12345, $request->getOrder()->getAmountOfMoney()->getAmount());
        self::assertNotNull($request->getOrder()->getReferences());
        self::assertSame('testOrder132', $request->getOrder()->getReferences()->getMerchantReference());
        self::assertNotNull($request->getCardPaymentMethodSpecificInput());
        self::assertSame(
            'https://shop.test.return.url',
            $request->getCardPaymentMethodSpecificInput()->getThreeDSecure()->getRedirectionData()->getReturnUrl()
        );
        self::assertNull($request->getOrder()->getDiscount());
        self::assertSame(
            'test@example.com',
            $request->getOrder()->getCustomer()->getContactDetails()->getEmailAddress()
        );
        self::assertSame('en_GB', $request->getOrder()->getCustomer()->getLocale());
        self::assertSame('DE', $request->getOrder()->getCustomer()->getBillingAddress()->getCountryCode());
        self::assertSame('Berlin', $request->getOrder()->getCustomer()->getBillingAddress()->getCity());
        self::assertSame('10171', $request->getOrder()->getCustomer()->getBillingAddress()->getZip());
        self::assertSame('Test street', $request->getOrder()->getCustomer()->getBillingAddress()->getStreet());
        self::assertSame('123A', $request->getOrder()->getCustomer()->getBillingAddress()->getHouseNumber());
        self::assertSame('', $request->getOrder()->getCustomer()->getBillingAddress()->getAdditionalInfo());
        self::assertSame('', $request->getOrder()->getCustomer()->getBillingAddress()->getState());
    }
}