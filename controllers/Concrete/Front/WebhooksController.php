<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Front;

use ModuleFrontController;
use CAWL\OnlinePayments\Classes\Utility\Tools;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\WebhookAPI\WebhookAPI;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionEntity;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
/**
 * Class WebhooksController
 *
 * @package OnlinePayments\Controllers\Concrete\Front
 */
class WebhooksController extends ModuleFrontController
{
    public function postProcess()
    {
        $requestData = \Tools::file_get_contents('php://input');
        $storeId = \Tools::getValue('storeId', null);
        if (null === $storeId && !empty($requestData)) {
            // Try to get store id from webhook payload
            $storeId = $this->getStoreIdFromPayload($requestData);
        }
        $response = WebhookAPI::get()->webhooks($storeId)->process($requestData, Tools::getServerHttpHeaders());
        if (!$response->isSuccessful()) {
            Logger::logError('Failed to process webhook.');
        }
    }
    private function getStoreIdFromPayload(string $requestData) : ?string
    {
        $merchantReference = '';
        $paymentId = '';
        $request = \json_decode($requestData, \true);
        if (!empty($request['payment']['paymentOutput']['references']['merchantReference'])) {
            $merchantReference = $request['payment']['paymentOutput']['references']['merchantReference'];
            $paymentId = $request['payment']['id'];
        }
        if (!empty($request['refund']['refundOutput']['references']['merchantReference'])) {
            $merchantReference = $request['refund']['refundOutput']['references']['merchantReference'];
            $paymentId = $request['refund']['id'];
        }
        if (!empty($request['paymentLink']['paymentLinkOrder']['merchantReference'])) {
            $merchantReference = $request['paymentLink']['paymentLinkOrder']['merchantReference'];
            $paymentId = (string) $request['paymentLink']['paymentId'];
        }
        if (empty($merchantReference)) {
            return (string) \Configuration::get('PS_SHOP_DEFAULT');
        }
        $queryFilter = new QueryFilter();
        $queryFilter->where('merchantReference', Operators::EQUALS, $merchantReference);
        if (!empty($paymentId)) {
            $queryFilter->where('transactionId', Operators::EQUALS, PaymentId::parse($paymentId)->getTransactionId());
        }
        $repository = RepositoryRegistry::getRepository(PaymentTransactionEntity::class);
        /** @var ?PaymentTransactionEntity $entity */
        $entity = $repository->selectOne();
        return $entity !== null ? $entity->getStoreId() : (string) \Configuration::get('PS_SHOP_DEFAULT');
    }
}
