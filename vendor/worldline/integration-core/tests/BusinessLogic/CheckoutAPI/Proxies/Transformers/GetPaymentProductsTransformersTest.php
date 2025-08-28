<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Proxies\Transformers;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\GetPaymentProductsParamsTransformer;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\GetPaymentProductsResponseTransformer;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockCartProvider;
use OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;
use OnlinePayments\Sdk\Merchant\Products\GetPaymentProductsParams;

/**
 * Class GetPaymentProductsParamsTransformerTest.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Proxies\RequestTransformers
 */
class GetPaymentProductsTransformersTest extends BaseTestCase
{
    public function testCartToGetPaymentProductsParamsTransformation(): void
    {
        $cart = (new MockCartProvider())->get();

        $expectedParams = new GetPaymentProductsParams();
        $expectedParams->setCountryCode('DE');
        $expectedParams->setAmount(12345);
        $expectedParams->setCurrencyCode('EUR');

        $actual = GetPaymentProductsParamsTransformer::transform($cart);

        self::assertSame($expectedParams->toArray(), $actual->toArray());
    }

    public function testGetPaymentProductsResponseToPaymentMethodCollection(): void
    {
        $expected = new PaymentMethodCollection([
            new PaymentMethod(PaymentProductId::alipay(), new TranslationCollection(new Translation('EN', 'AlipayPlus')), true),
            new PaymentMethod(PaymentProductId::americanExpress(),  new TranslationCollection(new Translation('EN', 'American Express')), true),
            new PaymentMethod(PaymentProductId::googlePay(),  new TranslationCollection(new Translation('EN', 'GOOGLEPAY')), true),
            new PaymentMethod(PaymentProductId::bankTransfer(),  new TranslationCollection(new Translation('EN', 'A2A')), true),
        ]);

        $actual = GetPaymentProductsResponseTransformer::transform((new GetPaymentProductsResponse())->fromObject(
            $this->getResponseJson('GetPaymentProductsResponse')
        ));

        self::assertEquals($expected, $actual);
    }

    /**
     * @param string $response
     * @return mixed
     */
    private function getResponseJson(string $response)
    {
        return json_decode(file_get_contents(__DIR__ . "/ApiResponses/$response.json"));
    }
}