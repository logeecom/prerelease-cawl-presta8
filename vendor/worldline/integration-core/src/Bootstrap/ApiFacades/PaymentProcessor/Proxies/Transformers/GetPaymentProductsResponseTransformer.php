<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;

/**
 * Class GetPaymentProductsResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class GetPaymentProductsResponseTransformer
{
    public static function transform(GetPaymentProductsResponse $response): PaymentMethodCollection
    {
        $result = new PaymentMethodCollection();
        foreach ($response->getPaymentProducts() ?? [] as $paymentProduct) {
            if (PaymentProductId::isSupported((string)$paymentProduct->id)) {
                $result->add(new PaymentMethod(
                    PaymentProductId::parse((string)$paymentProduct->id),
                    new TranslationCollection(new Translation('EN', $paymentProduct->displayHints->label)),
                    true
                ));
            }
        }

        return $result;
    }
}