<?php

namespace CheckoutAPI;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedCheckoutProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentMethodProxyInterface;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockCartProvider;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockHostedCheckoutProxy;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockPaymentMethodProxy;

/**
 * Class HostedCheckoutApiTest.
 *
 * @package CheckoutAPI
 */
class HostedCheckoutApiTest extends BaseTestCase
{
    private string $storeId = 'test123';
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestConnectionInDb();
    }

    public function testCreateHostedCheckoutSession(): void
    {
        $this->setupAvailablePaymentMethodsInApi(
            new PaymentMethodCollection([
                new PaymentMethod(PaymentProductId::hostedCheckout(), new TranslationCollection(new Translation('EN', 'Hosted Checkout')), true),
                new PaymentMethod(PaymentProductId::visa(), new TranslationCollection(new Translation('EN', 'Visa')), true),
            ])
        );
        $this->setupHostedCheckoutApiResponse(new PaymentResponse(
            new PaymentTransaction(
                'TEST_MERCHANT_REFERENCE',
                PaymentId::parse('1234567890_0'),
                'RETURN_HMAC'
            ),
            'https://test.hosted.checkout.url'
        ));

        $response = CheckoutAPI::get()->hostedCheckout($this->storeId)->createSession(new HostedCheckoutSessionRequest(
            new MockCartProvider(),
            'https://test.return.shop.checkout.url',
            PaymentProductId::bancontact()
        ));

        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertSame('https://test.hosted.checkout.url', $response->getRedirectUrl());
        self::assertSame('RETURN_HMAC', $response->getReturnHmac());
    }

    protected function setUpTestConnectionInDb(): void
    {
        $repository = RepositoryRegistry::getRepository(ConnectionConfigEntity::class);
        $repository->save(ConnectionConfigEntity::fromArray([
            'storeId' => $this->storeId,
            'connectionDetails' => [
                'mode' => (string)ConnectionMode::test(),
                'testCredentials' => [
                    'pspId' => 'TESTPSP',
                    'apiKey' => 'TESTAPIKEY',
                    'apiSecret' => 'TESTAPISECRET',
                    'webhookKey' => 'TESTWEBHOOKKEY',
                    'webhookSecret' => 'TESTWEBHOOKSECRET',
                ]
            ]
        ]));
    }

    protected function setupHostedCheckoutApiResponse(PaymentResponse $PaymentResponse): void
    {
        $proxy = new MockHostedCheckoutProxy($PaymentResponse);
        ServiceRegister::registerService(HostedCheckoutProxyInterface::class, static function () use ($proxy) {
            return $proxy;
        });
    }

    protected function setupAvailablePaymentMethodsInApi(?PaymentMethodCollection $paymentMethodCollection = null): void
    {
        $proxy = new MockPaymentMethodProxy($paymentMethodCollection ?? new PaymentMethodCollection());
        ServiceRegister::registerService(PaymentMethodProxyInterface::class, static function () use ($proxy) {
            return $proxy;
        });
    }
}