<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CreatePaymentLinkRequestTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CreatePaymentLinkResponseTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\ContextLogProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentLinksProxyInterface;
/**
 * Class PaymentLinksProxy.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies
 * @internal
 */
class PaymentLinksProxy implements PaymentLinksProxyInterface
{
    private MerchantClientFactory $clientFactory;
    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
    public function create(PaymentLinkRequest $request, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, PayByLinkSettings $payByLinkSettings, PaymentMethodCollection $paymentMethodCollection) : PaymentLinkResponse
    {
        ContextLogProvider::getInstance()->setCurrentOrder($request->getCartProvider()->get()->getMerchantReference());
        return CreatePaymentLinkResponseTransformer::transform($this->clientFactory->get()->paymentLinks()->createPaymentLink(CreatePaymentLinkRequestTransformer::transform($request, $cardsSettings, $paymentSettings, $payByLinkSettings, $paymentMethodCollection)));
    }
    public function getById(string $paymentLinkId, string $merchantReference) : PaymentLinkResponse
    {
        ContextLogProvider::getInstance()->setCurrentOrder($merchantReference);
        return CreatePaymentLinkResponseTransformer::transform($this->clientFactory->get()->paymentLinks()->getPaymentLinkById($paymentLinkId));
    }
}
