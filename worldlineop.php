<?php
/**
 * 2021 Online Payments
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 * @author    PrestaShop partner
 * @copyright 2021 Online Payments
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use OnlinePayments\Classes\OnlinePaymentsModule;

if (!defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__ . '/bootstrap.php';

/**
 * Class Worldlineop
 */
class Worldlineop extends OnlinePaymentsModule
{
    /**
     * Worldlineop constructor.
     */
    public function __construct()
    {
        $this->name = 'worldlineop';
        $this->author = 'Worldline Online Payments';
        $this->version = '3.0.0';
        $this->tab = 'payments_gateways';
        $this->module_key = '089d13d0218de8085259e542483f4438';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        parent::__construct();
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8', 'max' => '8.2.99'];
        //@formatter:off
        $this->displayName = $this->l('Worldline Online Payments');
        $this->description = $this->l('This module offers a 1-click integration to start accepting payments and grow your revenues by offering your customers with global and regional payment methods to sell across Europe.');
        //@formatter:on
    }
}
