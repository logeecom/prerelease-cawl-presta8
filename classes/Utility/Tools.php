<?php

namespace CAWL\OnlinePayments\Classes\Utility;

use Currency;
use Customer;
use Language;
use Mail;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use Order;
use Symfony\Component\Filesystem\Filesystem;
use Validate;
/**
 * Class Tools
 * @internal
 */
class Tools
{
    /**
     * @param string $source
     * @param string $destination
     */
    public static function copy(string $source, string $destination) : void
    {
        $filesystem = new Filesystem();
        $filesystem->copy($source, $destination, \true);
    }
    /**
     * @return array
     */
    public static function getServerHttpHeaders() : array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (\Tools::substr($key, 0, 5) !== 'HTTP_') {
                continue;
            }
            $header = \str_replace(' ', '-', \ucwords(\str_replace('_', ' ', \Tools::strtolower(\Tools::substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }
    /**
     * @param int $idCurrency
     *
     * @return string
     */
    public static function getIsoCurrencyCodeById(int $idCurrency) : string
    {
        $currency = new Currency($idCurrency);
        if (!Validate::isLoadedObject($currency)) {
            return '';
        }
        return $currency->iso_code;
    }
    /**
     * @param string $isoCode
     *
     * @return Currency|false
     */
    public static function getCurrencyByIsoCode(string $isoCode)
    {
        $dbQuery = new \DbQuery();
        $dbQuery->select('id_currency')->from('currency')->where('iso_code = "' . pSQL($isoCode) . '"');
        $idCurrency = \Db::getInstance(\_PS_USE_SQL_SLAVE_)->getValue($dbQuery);
        $currency = new Currency((int) $idCurrency);
        return \Validate::isLoadedObject($currency) ? $currency : \false;
    }
    /**
     * @param int $idOrder
     *
     * @return bool|int
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function sendPendingCaptureMail(int $idOrder)
    {
        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            return \false;
        }
        $subjects = ['en' => 'Awaiting payment capture'];
        $language = new Language((int) $order->id_lang);
        $customer = new Customer((int) $order->id_customer);
        /** @var OnlinePaymentsModule $module */
        $module = ServiceRegister::getService(\Module::class);
        return Mail::send($order->id_lang, 'pending_capture', isset($subjects[$language->iso_code]) ? $subjects[$language->iso_code] : $subjects['en'], ['{order_name}' => $order->reference, '{firstname}' => $customer->firstname, '{lastname}' => $customer->lastname], $customer->email, null, null, null, null, null, $module->getLocalPath() . 'mails/');
    }
}
