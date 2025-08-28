<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Proxies\Transformers;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CreateHostedTokenizationRequestTransformer;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CreateHostedTokenizationResponseTransformer;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockCartProvider;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationResponse;

/**
 * Class CreateHostedTokenizationTransformersTest.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Proxies\Transformers
 */
class CreateHostedTokenizationTransformersTest extends BaseTestCase
{
    public function testCartToCreateHostedTokenizationRequest(): void
    {
        $cart = (new MockCartProvider())->get();

        $expected = new CreateHostedTokenizationRequest();
        $expected->setAskConsumerConsent(true);
        $expected->setLocale('en_GB');

        $actual = CreateHostedTokenizationRequestTransformer::transform($cart);

        self::assertEquals($expected, $actual);
    }

    public function testCreateHostedTokenizationResponseToHostedTokenization(): void
    {
        $expected = new HostedTokenization('https://test.hostedtokenization.url/form/123saa', []);

        $actual = CreateHostedTokenizationResponseTransformer::transform((new CreateHostedTokenizationResponse())->fromObject(
            $this->getResponseJson('CreateHostedTokenizationResponse')
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