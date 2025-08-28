<?php

namespace OnlinePayments\Controllers\Concrete\Front;

use Exception;
use ModuleFrontController;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Exceptions\TokenDeletionFailureException;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Exceptions\TokenNotFoundException;
use PrestaShopException;
use Tools;

/**
 * Class CustomerAccountExtensionController
 *
 * @package OnlinePayments\Controllers\Concrete\Front
 */
class CustomerAccountExtensionController extends ModuleFrontController
{
    protected bool $redirectStoredCards = false;

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initContent(): void
    {
        if ($this->redirectStoredCards) {
            $this->redirectWithNotifications($this->context->link->getModuleLink('worldlineop', 'storedcards', []));
        }

        parent::initContent();

        $storedCards = [];

        if ($this->context->customer->id) {
            $storedCards = CheckoutAPI::get()->hostedTokenization($this->context->shop->id)
                ->getTokens($this->context->customer->id)->toArray();
        }

        $this->context->smarty->assign([
            'stored_cards' => $storedCards,
            'img_path' => rtrim($this->context->shop->getBaseURL(), '/') . $this->module->getPathUri() .
                'views/img/'
        ]);

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/storedcards.tpl');
    }

    /**
     * @return void
     */
    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            $this->module->name . '-storedcards',
            $this->module->getPathUri() . 'views/css/storedcards.css',
            ['server' => 'remote']
        );
    }

    public function postProcess()
    {
        if (Tools::getValue('delete')) {
            $this->deleteCard();
        }

        parent::postProcess();
    }

    /**
     * @return bool
     *
     * @throws TokenDeletionFailureException
     * @throws TokenNotFoundException
     */
    public function deleteCard(): bool
    {
        $idStoreCard = Tools::getValue('id_token');
        $tokenSent = Tools::getValue('token');
        $tokenCalculated = Tools::getToken(true, $this->context);
        if (!$idStoreCard || !$tokenSent || $tokenSent != $tokenCalculated) {
            $this->errors[] = $this->module->l('Could not delete stored card.', 'storedcards');

            return false;
        }

        $response = CheckoutAPI::get()->hostedTokenization($this->context->shop->id)
            ->deleteToken($this->context->customer->id, $idStoreCard);

        if ($response->isSuccessful()) {
            $this->success[] = $this->module->l('Card deleted successfully.', 'storedcards');
            $this->redirectStoredCards = true;

            return true;
        }

        $this->errors[] = $this->module->l('Could not delete stored card.', 'storedcards');

        return false;
    }
}
