<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod\PaymentMethodConfigEntity;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentMethodProxyInterface;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockCartProvider;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockPaymentMethodProxy;

/**
 * Class CheckoutPaymentMethodsApiTest.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI
 */
class CheckoutPaymentMethodsApiTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupAvailablePaymentMethodsInApi();
    }

    private string $storeId = 'test123';

    public function testThereShouldBeNoAvailablePaymentMethodsWithoutActiveConnection(): void
    {
        $response = CheckoutAPI::get()->paymentMethods($this->storeId)->getAvailablePaymentMethods(new MockCartProvider());

        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertTrue($response->getPaymentMethods()->isEmpty());
    }

    public function testThereShouldBeNoAvailablePaymentMethodsIfNoPaymentMethodIsEnabled(): void
    {
        $this->setUpTestConnectionInDb();

        $response = CheckoutAPI::get()->paymentMethods($this->storeId)->getAvailablePaymentMethods(new MockCartProvider());

        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertTrue($response->getPaymentMethods()->isEmpty());
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

    public function testOnlyEnabledPaymentMethodsShouldBeAvailable(): void
    {
        $this->setUpTestConnectionInDb();
        $this->setUpEnabledPaymentMethodInDb(PaymentProductId::mastercard(), 'Master Card', true);
        $this->setUpEnabledPaymentMethodInDb(PaymentProductId::visa(), 'Visa', false);
        $this->setupAvailablePaymentMethodsInApi(new PaymentMethodCollection([
            new PaymentMethod(PaymentProductId::mastercard(), new TranslationCollection(new Translation('EN', 'Master Card')), true),
            new PaymentMethod(PaymentProductId::visa(), new TranslationCollection(new Translation('EN', 'Visa')), true),
        ]));

        $response = CheckoutAPI::get()->paymentMethods($this->storeId)->getAvailablePaymentMethods(new MockCartProvider());

        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertTrue($response->getPaymentMethods()->has(PaymentProductId::mastercard()));
        self::assertFalse($response->getPaymentMethods()->has(PaymentProductId::visa()));
    }

    public function testOnlyEnabledPaymentMethodsMatchedByAPIShouldBeAvailable(): void
    {
        $this->setUpTestConnectionInDb();
        $this->setUpEnabledPaymentMethodInDb(PaymentProductId::mastercard(), 'Master Card', true);
        $this->setUpEnabledPaymentMethodInDb(PaymentProductId::visa(), 'Visa', false);
        $this->setupAvailablePaymentMethodsInApi(new PaymentMethodCollection([
            new PaymentMethod(PaymentProductId::googlePay(), new TranslationCollection(new Translation('EN', 'GooglePay')), true),
        ]));

        $response = CheckoutAPI::get()->paymentMethods($this->storeId)->getAvailablePaymentMethods(new MockCartProvider());

        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertTrue($response->getPaymentMethods()->isEmpty());
    }

    public function testGenericPaymentMethodsShouldBeAvailableIfEnabledRegardlessOfApiResponse(): void
    {
        $this->setUpTestConnectionInDb();
        $this->setUpEnabledPaymentMethodInDb(PaymentProductId::hostedCheckout(), 'Redirection', true);
        $this->setUpEnabledPaymentMethodInDb(PaymentProductId::cards(), 'Cards', false);
        $this->setupAvailablePaymentMethodsInApi(new PaymentMethodCollection([
            new PaymentMethod(PaymentProductId::mastercard(), new TranslationCollection(new Translation('EN', 'Master Card')), true),
            new PaymentMethod(PaymentProductId::visa(), new TranslationCollection(new Translation('EN', 'Visa')), true),
        ]));

        $response = CheckoutAPI::get()->paymentMethods($this->storeId)->getAvailablePaymentMethods(new MockCartProvider());

        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertTrue($response->getPaymentMethods()->has(PaymentProductId::hostedCheckout()));
        self::assertFalse($response->getPaymentMethods()->has(PaymentProductId::cards()));
    }

    protected function setUpEnabledPaymentMethodInDb(
        PaymentProductId $paymentProductId,
        string $paymentName,
        bool $enabled
    ): void {
        $repository = RepositoryRegistry::getRepository(PaymentMethodConfigEntity::class);
        $repository->save(PaymentMethodConfigEntity::fromArray([
            'storeId' => $this->storeId,
            'mode' => (string)ConnectionMode::test(),
            'enabled' => $enabled,
            'paymentProductId' => (string)$paymentProductId,
            'paymentMethod' => [
                'paymentProductId' => (string)$paymentProductId,
                'nameTranslations' => [
                    0 => [
                        'language' => 'EN',
                        'translation' => $paymentName
                    ]
                ],
                'enabled' => $enabled,
            ]
        ]));
    }

    protected function setupAvailablePaymentMethodsInApi(?PaymentMethodCollection $paymentMethodCollection = null): void
    {
        $proxy = new MockPaymentMethodProxy($paymentMethodCollection ?? new PaymentMethodCollection());
        ServiceRegister::registerService(PaymentMethodProxyInterface::class, static function () use ($proxy) {
            return $proxy;
        });
    }
}