<?php

namespace {
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
    use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
    if (!\defined('_PS_VERSION_')) {
        exit;
    }
    require_once __DIR__ . '/bootstrap.php';
    /**
     * Class Cawlop
     * @internal
     */
    class Cawlop extends OnlinePaymentsModule
    {
        public function __construct()
        {
            $config = \json_decode(\file_get_contents(__DIR__ . '/config.json'), \true);
            $this->name = $config['MODULE_NAME'];
            $this->author = $config['AUTHOR'];
            $this->version = $config['VERSION'];
            $this->tab = 'payments_gateways';
            $this->module_key = $config['MODULE_KEY'];
            $this->currencies = \true;
            $this->currencies_mode = 'checkbox';
            parent::__construct();
            $this->bootstrap = \true;
            $this->ps_versions_compliancy = ['min' => '8', 'max' => '8.2.99'];
            //@formatter:off
            $this->displayName = $config['DISPLAY_NAME'];
            $this->description = $this->l('This module offers a 1-click integration to start accepting payments and grow your revenues by offering your customers with global and regional payment methods to sell across Europe.');
            //@formatter:on
        }
    }
    /**
     * Class Cawlop
     * @internal
     */
    \class_alias('CAWL\\Cawlop', 'Cawlop', \false);
}
