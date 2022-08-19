<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
require_once dirname(__FILE__) . "/classes/HrxDb.php";
require_once dirname(__FILE__) . "/classes/HrxAPIHelper.php";
require_once dirname(__FILE__) . "/classes/HrxData.php";
require_once dirname(__FILE__) . "/classes/modules/HrxOrder.php";
require_once dirname(__FILE__) . "/classes/modules/HrxWarehouse.php";
require_once dirname(__FILE__) . "/classes/modules/HrxCartTerminal.php";
require_once dirname(__FILE__) . "/classes/modules/HrxShippingPrice.php";

require_once dirname(__FILE__) . "/vendor/autoload.php";

if (!defined('_PS_VERSION_')) {
    exit;
}

class HrxDelivery extends CarrierModule
{
    const CONTROLLER_WAREHOUSE = 'AdminHrxWarehouse';
    const CONTROLLER_ORDER = 'AdminHrxOrder';
    const CONTROLLER_ADMIN_AJAX = 'AdminHrxDeliveryAjax';

    const CARRIER_TYPE_PICKUP = "pickup";
    const CARRIER_TYPE_COURIER = "courier";

    /**
     * List of hooks
     */
    protected $_hooks = array(
        'header',
        'displayCarrierExtraContent',
        'updateCarrier',
        'displayAdminOrder',
        'actionValidateStepComplete',
        'actionValidateOrder',
        'actionAdminControllerSetMedia',
        'displayAdminListBefore',
        'actionCarrierProcess',
        'displayBeforeCarrier',
        'actionObjectCountryUpdateAfter',
        'backOfficeHeader',
    );

    public static $_order_states = array(
        'cancelled' => array(
            'key' => 'HRX_ORDER_STATE_CANCELLED',
            'color' => '#FAE9EA',
            'text-color' => '#E00000',
            'lang' => array(
                'en' => 'Cancelled',
                'lt' => 'HRX siunta atšaukta',
            ),
        ),
        'delivered' => array(
            'key' => 'HRX_ORDER_STATE_DELIVERED',
            'color' => '#E7F4EA',
            'text-color' => '#44A04C',
            'lang' => array(
                'en' => 'Delivered',
                'lt' => 'HRX siunta pristatyta',
            ),
        ),
        'error' => array(
            'key' => 'HRX_ORDER_STATE_ERROR',
            'color' => '#FAE9EA',
            'text-color' => '#E00000',
            'lang' => array(
                'en' => 'Error',
                'lt' => 'Klaida HRX siuntoje',
            ),
        ),
        'in_delivery' => array(
            'key' => 'HRX_ORDER_STATE_IN_DELIVERY',
            'color' => '#FAF1DF',
            'text-color' => '#F3AC20',
            'lang' => array(
                'en' => 'In delivery',
                'lt' => 'HRX siunta pristatoma',
            ),
        ),
        'being_returned' => array(
            'key' => 'HRX_ORDER_STATE_BEING_RETURNED',
            'color' => '#FAF1DF',
            'text-color' => '#F3AC20',
            'lang' => array(
                'en' => 'In return',
                'lt' => 'HRX siunta grąžinama',
            ),
        ),
        'new' => array(
            'key' => 'HRX_ORDER_STATE_NEW',
            'color' => '#E6F6FA',
            'text-color' => '#25B9D7',
            'lang' => array(
                'en' => 'New',
                'lt' => 'Nauja HRX siunta',
            ),
        ),
        'ready' => array(
            'key' => 'HRX_ORDER_STATE_READY',
            'color' => '#E7F4EA',
            'text-color' => '#44A04C',
            'lang' => array(
                'en' => 'Ready',
                'lt' => 'HRX siunta paruošta',
            ),
        ),
        'returned' => array(
            'key' => 'HRX_ORDER_STATE_RETURNED',
            'color' => '#E7F4EA',
            'text-color' => '#44A04C',
            'lang' => array(
                'en' => 'Returned',
                'lt' => 'HRX siunta grąžinta',
            ),
        ),
    );

    public static $_configKeys = array(
        'API' => [
            'token' => 'HRX_DELIVERY_API_TOKEN',
            'mode'  => 'HRX_DELIVERY_LIVE_MODE',
        ],
        'DELIVERY' => [
            'w'      => 'HRX_DEFAULT_WIDTH',
            'h'      => 'HRX_DEFAULT_HEIGHT',
            'l'      => 'HRX_DEFAULT_LENGTH',
            'weight' => 'HRX_DEFAULT_WEIGHT',
        ],
        'ADVANCED' => [
            'return_label'  => 'HRX_REQUIRE_RETURN_LABEL',
            'passphrase'    => 'HRX_CARRIER_DISABLE_PASSPHRASE',
        ],
        'PRICE' => [
            'use_tax_table' => 'HRX_TAX_TABLE_ENABLED',
            'tax' => 'HRX_SHIPPING_PRICE_TAX',
            'or_amount' => 'HRX_SHIPPING_PRICE_TAX_AMOUNT', //if false, when use percentage tax
        ]
    );

    public static $_carriers = array(
        self::CARRIER_TYPE_PICKUP => array(
            'type' => 'pickup',
            'id_name' => 'HRX_PICKUP_ID',
            'reference_name' => 'HRX_PICKUP_ID_REFERENCE',
            'title' => 'HRX parcel terminal',
            'image' => 'logo.png',
            'kind' => 'delivery_location'
        ),
        self::CARRIER_TYPE_COURIER => array(
            'type' => 'courier',
            'id_name' => 'HRX_COURIER_ID',
            'reference_name' => 'HRX_COURIER_ID_REFERENCE',
            'title' => 'HRX courier',
            'image' => 'logo.png',
            'kind' => 'courier'
        ),
    );

    public static $_moduleDir = _PS_MODULE_DIR_ . 'hrxdelivery/';
    public static $_labelPdfDir = 'hrxdelivery/pdf/shippingLabels/';
    public static $_returnLabelPdfDir = 'hrxdelivery/pdf/returnLabels/';

    public $terminal_count;

    public function __construct()
    {
        $this->name = 'hrxdelivery';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'mijora.lt';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('HRX delivery');
        $this->description = $this->l('Shipping module for HRX delivery method');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        if (!parent::install()) {
            $this->_errors[] = $this->l('Failed to install the module');
            return false;
        }

        foreach ($this->_hooks as $hook) {
            if (!$this->registerHook($hook)) {
                $this->_errors[] = $this->l('Failed to install hook') . ' ' . $hook . '.';
                return false;
            }
        }

        if (!$this->createDbTables()) {
            $this->_errors[] = $this->l('Failed to create tables.');
            return false;
        }

        if (!$this->addOrderStates()) {
            $this->_errors[] = $this->l('Failed to order states.');
            return false;
        }

        foreach (self::$_carriers as $carrier) 
        {
            if ($carrier = $this->addCarrier($carrier['id_name'], $carrier['title'], $carrier['image'])) 
            {
                $this->addZones($carrier);
                $this->addGroups($carrier);
                $this->addRanges($carrier);
            }
            else
            {
                $this->_errors[] = $this->l('Failed to create carrier') . ' ' . $carrier['id_name'] . '.';
                return false;
            }
        }
        
        Configuration::updateValue(self::$_configKeys['API']['mode'], false);

        foreach(self::$_configKeys['PRICE'] as $key){
            Configuration::updateValue($key, 0);
        }

        $this->registerTabs();

        return true;

    }

    /**
     * Create module database tables
     */
    public function createDbTables()
    {
        try {
            $cDb = new HrxDb();

            $result = $cDb->createTables();
        } catch (Exception $e) {
            $result = false;
        }
        return $result;
    }

    public function uninstall()
    {
        $cDb = new HrxDb();
        $cDb->deleteTables();

        $this->deleteTabs();

        foreach (self::$_carriers as $carrier) {
            if (!$this->deleteCarrier($carrier['id_name'])) {
                $this->_errors[] = $this->l('Failed to delete carrier') . ' ' . $carrier['id_name'] . '.';
                return false;
            }
        }

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {        
        $output = null;

        if (Tools::isSubmit('submit' . $this->name . 'api')) {
            $output .= $this->saveConfig('API', $this->l('API settings updated'));
        }
        if (Tools::isSubmit('submit' . $this->name . 'delivery')) {
            $output .= $this->saveConfig('DELIVERY', $this->l('Delivery settings updated'));
        }
        if (Tools::isSubmit('submit' . $this->name . 'price')) {
            $output .= $this->saveConfig('PRICE', $this->l('Shipping price settings updated'));
        }
        if (Tools::isSubmit('submit' . $this->name . 'advanced')) {
            $output .= $this->saveConfig('ADVANCED', $this->l('Advanced settings updated'));
        }

        return $output
            . $this->displayConfigApi()
            . $this->displayConfigDelivery()
            . $this->displayConfigPrice()
            . $this->displayConfigAdvancedSettings()
            . $this->displayConfigTerminalSettings();
    }

    /**
     * Save section values in module configuration
     */
    public function saveConfig($section_id, $success_message = '')
    {
        $output = null;

        foreach (self::$_configKeys[strtoupper($section_id)] as $id => $key) 
        {
            $value = Tools::getValue($key);

            if($section_id == 'DELIVERY')
            {
                if((float)$value <= 0 || !is_numeric($value)){
                    return $this->displayWarning('Dimensions should be positive numbers');
                }
            }
            if($section_id == 'PRICE' && $id == 'tax')
            {
                if((float)$value < 0 || !is_numeric($value)){
                    return $this->displayWarning('Tax should be positive number');
                }
            }

            Configuration::updateValue($key, $value);
        }
        $success_message = (!empty($success_message)) ? $success_message : $this->l('Settings updated');
        $output .= $this->displayConfirmation($success_message);
        
        return $output;
    }

    /**
     * Display API section in module configuration
     */
    public function displayConfigApi()
    {
        $section_id = 'API';

        $form_fields = array(
            array(
                'type' => 'switch',
                'label' => $this->l('Live mode'),
                'name' => self::$_configKeys[$section_id]['mode'],
                'is_bool' => true,
                'desc' => $this->l('Use this module in live mode'),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                ),
            ),
            array(
                'type' => 'text',
                'prefix' => '<i class="icon-key"></i>',
                'name' => self::$_configKeys[$section_id]['token'],
                'label' => $this->l('API token'),
            ),
        );

        return $this->displayConfig($section_id, $this->l('API settings'), $form_fields, $this->l('Save API settings'));
    }
    

    /**
     * Display DELIVERY section in module configuration
     */
    public function displayConfigDelivery()
    {
        $section_id = 'DELIVERY';

        $dimensions_fields = array(
            array(
                'name' => self::$_configKeys[$section_id]['w'],
                'label' => $this->l('Width'),
                'value' => Configuration::get(self::$_configKeys[$section_id]['w']),
                'description' => $this->l('Used when the product has no dimension'),
                'unit' => 'cm'
            ),
            array(
                'name' => self::$_configKeys[$section_id]['h'],
                'label' => $this->l('Height'),
                'value' => Configuration::get(self::$_configKeys[$section_id]['h']),
                'unit' => 'cm'
            ),
            array(
                'name' => self::$_configKeys[$section_id]['l'],
                'label' => $this->l('Length'),
                'value' => Configuration::get(self::$_configKeys[$section_id]['l']),
                'unit' => 'cm'
            ),
            array(
                'name' => self::$_configKeys[$section_id]['weight'],
                'label' => $this->l('Weight'),
                'value' => Configuration::get(self::$_configKeys[$section_id]['weight']),
                'class' => 'weight',
                'unit' => 'kg'
            ),
        );

        $button = array(
            'text' => $this->l('Save delivery settings')
        );

        $this->context->smarty->assign([
                'version17' => version_compare(_PS_VERSION_, '1.7', '>='),
                'legend' => $this->l('Delivery settings'),
                'dimensions_fields' => $dimensions_fields,
                'dimensions_group' => $this->l('Default dimensions'),
                'button' => $button,
                'action' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ]
        );

        return $this->context->smarty->fetch(self::$_moduleDir . 'views/templates/admin/delivery_settings.tpl');
    }

    /**
     * Display PRICE section in module configuration
     */
    public function displayConfigPrice()
    {
        $section_id = 'PRICE';

        $config_fields = array(
            'use_tax_table' => array(
                'name' => self::$_configKeys[$section_id]['use_tax_table'],
                'label' => $this->l('Use price table'),
                'value' => Configuration::get(self::$_configKeys[$section_id]['use_tax_table']),
                'description' => $this->l('If it is enabled, use this shipping price table, otherwise prestashop carrier settings will be used'),
            ),
            'tax' => array(
                'name' => self::$_configKeys[$section_id]['tax'],
                'label' => $this->l('Tax'),
                'value' => Configuration::get(self::$_configKeys[$section_id]['tax']),
                'description' => $this->l('Add specified tax to shipping price'),
            ),
            'or_amount' => array(
                'name' => self::$_configKeys[$section_id]['or_amount'],
                'label' => $this->l('Use amount tax'),
                'value' => Configuration::get(self::$_configKeys[$section_id]['or_amount']),
                'description' => $this->l('If it is enabled, the amount of tax is added to the shipping price, otherwise a percentage fee is added'),
            ),
        );

        $shipping_price_table = HrxShippingPrice::getAllTable();

        $button = array(
            'text' => $this->l('Save price settings')
        );

        $this->context->smarty->assign([
                'version17' => version_compare(_PS_VERSION_, '1.7', '>='),
                'legend' => $this->l('Shipping price settings'),
                'config_fields' => $config_fields,
                'shipping_price_table' => $shipping_price_table,
                'button' => $button,
                'action' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'currency' => $this->context->currency->iso_code,
            ]
        );

        return $this->context->smarty->fetch(self::$_moduleDir . 'views/templates/admin/price_settings.tpl');
    }

    /**
     * Display Advanced settings section in module configuration
     */
    public function displayConfigAdvancedSettings()
    {
        $section_id = 'ADVANCED';

        $form_fields = array(
            array(
                'type' => 'switch',
                'label' => $this->l('Require return label'),
                'name' => self::$_configKeys[$section_id]['return_label'],
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                ),
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Carrier disable passphrase'),
                'name' => self::$_configKeys[$section_id]['passphrase'],
                'desc' => $this->l('Carriers will not be used for the cart, if cart contains any product, whose description contains this passphrase.'),
            ),
        );

        return $this->displayConfig($section_id, $this->l('Advanced settings'), $form_fields, $this->l('Save Advanced settings'));
    }

    public function displayConfigTerminalSettings()
    {
            $this->context->smarty->assign([
                'version17' => version_compare(_PS_VERSION_, '1.7', '>='),
                'legend' => $this->l('Terminal Settings'),
                'hrx_update_terminals_url' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=updateTerminals',
                ]
            );

        return $this->context->smarty->fetch(self::$_moduleDir . 'views/templates/admin/terminal_settings.tpl');
    }

    /**
     * Build section display in module configuration
     */
    public function displayConfig($section_id, $section_title, $form_fields = array(), $submit_title = '')
    {
        $fieldsForm[0]['form'] = array(
            'legend' => array(
                'title' => $section_title,
            ),
            'input' => $form_fields,
            'submit' => array(
                'title' => (!empty($submit_title)) ? $submit_title : $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ),
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->bootstrap = true;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name . strtolower($section_id);
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load saved settings
        if (isset(self::$_configKeys[strtoupper($section_id)])) {
            foreach (self::$_configKeys[strtoupper($section_id)] as $key) {
                $helper->fields_value[$key] = Configuration::get($key);
            }
        }

        return $helper->generateForm($fieldsForm);
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        if($params instanceof Cart)
        {
            $cart = $params;

            if($this->checkCarrierDisablePassphrase($cart))
                return false;

            $address = new Address($cart->id_address_delivery);
            $country_code = Country::getIsoById($address->id_country);

            //Check pickup carrier, if there are any terminals for cart
            if(!isset($this->terminal_count))
            {
                $filtered_terminals = HrxData::getTerminalsByCountry($country_code);
                $this->terminal_count = count($filtered_terminals);
            }

            if($this->terminal_count == 0)
                return false; 

            if(Configuration::get(self::$_configKeys['PRICE']['use_tax_table']))
            {
                $weight = $cart->getTotalWeight();

                $price_by_weight = HrxShippingPrice::getPrice($weight, $country_code);
                if($price_by_weight){
                    $tax = Configuration::get(self::$_configKeys['PRICE']['tax']);
                    if(Configuration::get(self::$_configKeys['PRICE']['or_amount'])){
                        $price_by_weight += $tax;
                    }else{
                        $price_by_weight = ($price_by_weight * (1 + $tax / 100));
                    }
                    $shipping_cost = $price_by_weight;
                }
            }
        }

        return $shipping_cost;
    }

     /**
     * Check if cart contains products, whose description contains @carrier_disable_passphrase
     */
    private function checkCarrierDisablePassphrase($cart)
    {
        $carrier_disable_passphrase = Configuration::get(self::$_configKeys['ADVANCED']['passphrase']);
        if($carrier_disable_passphrase)
        {
            $cart_products = $cart->getProducts();
            $id_lang = $this->context->language->id;
            foreach ($cart_products as $product)
            {
                // Cart products don't have description, so get it.
                $product_description = Db::getInstance()->getValue(
                    (new DbQuery())
                        ->select('description')
                        ->from('product_lang')
                        ->where('`id_product` = ' . $product['id_product'] . ' AND `id_lang` = ' . $id_lang)
                );

                /**
                 * Carrier cannot be used for the cart, if cart contains any product, whose description contains @carrier_disable_passphrase
                 */
                if(strpos($product_description, $carrier_disable_passphrase) !== false)
                    return true;
            }
        }
        return false;
    }

    public function getOrderShippingCostExternal($params)
    {
        return true;
    }

    protected function addCarrier($key, $name, $image = '')
    {
        $carrier = new Carrier();

        $carrier->name = $name;
        $carrier->is_module = true;
        $carrier->active = 1;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang)
            $carrier->delay[$lang['id_lang']] = $this->l('1-2 business days');

        if ($carrier->add() == true)
        {
            try {
                $image_path = self::$_moduleDir . 'views/img/' . $image;
                $image_path = (empty($image)) ? self::$_moduleDir . 'logo.png' : $image_path;
    
                copy($image_path, _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg');
            } catch (Exception $e) {
            }
            Configuration::updateValue($key, $carrier->id);
            Configuration::updateValue($key . '_REFERENCE', (int)$carrier->id);
            return $carrier;
        }

        return false;
    }

    protected function addGroups($carrier)
    {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group)
            $groups_ids[] = $group['id_group'];

        $carrier->setGroups($groups_ids);
    }

    protected function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected function addZones($carrier)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone)
            $carrier->addZone($zone['id_zone']);
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('configure') == $this->name || Tools::getValue('controller') == 'AdminOrders' || Tools::getValue('controller') == 'AdminHrxOrder') 
        {
            Media::addJsDef([
                'hrxdelivery_create_order_url' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=createOrder',
                'hrxdelivery_save_order_url' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=saveOrder',
                'hrxdelivery_update_terminal_list' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=updateTerminalList',
                'hrxdelivery_print_label_url' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=printLabel',
                'hrxdelivery_cancel_order_url' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=cancelOrder',
                'hrxdelivery_update_ready_state' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=updateReadyState',
                'hrxdelivery_update_price_table' => $this->context->link->getAdminLink(self::CONTROLLER_ADMIN_AJAX) . '&action=updatePriceTable',
            ]);
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        if(version_compare(_PS_VERSION_, '1.7', '>='))
        {
            $add_content = $this->context->controller->php_self == 'order' && !$this->check17PaymentStep($this->context->cart);
        }
        // 1.6
        else
        {
            $add_content = ($this->context->controller->php_self == 'order' && isset($this->context->controller->step) && $this->context->controller->step != 3) ||  $this->context->controller->php_self == 'order-opc';
        }

        $carrier = new Carrier((int)Configuration::get(self::$_carriers['pickup']['id_name']));

        Media::addJsDef([
            'hrxdelivery_front_controller_url' => $this->context->link->getModuleLink($this->name, 'front'),
            'hrx_carrier_pickup' => $carrier->id,
            'hrx_change_terminal'=> $this->l('Change terminal'),
            'hrx_terminal_map_translations' => [
                'modal_header'=> $this->l('Terminal map'),
                'terminal_list_header' => $this->l('Terminal list'),
                'seach_header' => $this->l('Search around'),
                'search_btn' => $this->l('Find'),
                'modal_open_btn' => $this->l('Choose terminal'),
                'geolocation_btn' => $this->l('Use my location'),
                'your_position' => $this->l('Distance calculated from this point'),
                'nothing_found' => $this->l('Nothing found'),
                'no_cities_found' => $this->l('There were no cities found for your search term'),
                'geolocation_not_supported' => $this->l('Geolocation is not supported'),
                'select_pickup_point' => $this->l('Choose from more then 100 parcel terminals'),
                'search_placeholder' => $this->l('Type address'),
                'select_btn' => $this->l('Choose terminal'),
            ],
        ]);

        if ($add_content)
        {
            Media::addJsDef(array(
                    'images_url' => $this->_path . 'dist/images/',
                )
            );
            if(version_compare(_PS_VERSION_, '1.7', '>=')){               
                $this->context->controller->registerJavascript('modules-hrx-terminals-mapping-js', 'modules/' . $this->name . '/views/js/terminal-mapping.js');
                $this->context->controller->registerJavascript('modules-hrx-map-init-js', 'modules/' . $this->name . '/views/js/map-init.js');
                $this->context->controller->registerJavascript('hrxdelivery-js', 'modules/' . $this->name . '/views/js/front17.js');
            }
            else{
                $this->context->controller->addJS('modules/' . $this->name . '/views/js/terminal-mapping.js');
                $this->context->controller->addJS('modules/' . $this->name . '/views/js/front16.js');
                $this->context->controller->addJS('modules/' . $this->name . '/views/js/map-init.js');
            }
            
            $this->context->controller->addCSS($this->_path.'views/css/front.css');
            $this->context->controller->addCSS($this->_path . 'views/css/terminal-mapping.css');
        }
        
    }

    private function getModuleTabs()
    {
        return array(
            self::CONTROLLER_ORDER => array(
                'title' => $this->l('HRX Orders'),
                'parent_tab' => (int) Tab::getIdFromClassName('AdminParentShipping')
            ),
            self::CONTROLLER_WAREHOUSE => array(
                'title' => $this->l('HRX Warehouses'),
                'parent_tab' => (int) Tab::getIdFromClassName('AdminParentShipping')
            ),
            self::CONTROLLER_ADMIN_AJAX => array(
                'title' => $this->l('HRX Terminals'),
                'parent_tab' => -1
            ),
        );
    }

    private function addOrderStates()
    {

        foreach (self::$_order_states as $os)
        {
            $order_state = (int)Configuration::get($os['key']);
            $order_status = new OrderState($order_state, (int)Context::getContext()->language->id);

            if (!$order_status->id || !$order_state) {
                $orderState = new OrderState();
                $orderState->name = array();
                foreach (Language::getLanguages() as $language) {
                    if (strtolower($language['iso_code']) == 'lt')
                        $orderState->name[$language['id_lang']] = $os['lang']['lt'];
                    else
                        $orderState->name[$language['id_lang']] = $os['lang']['en'];
                }
                $orderState->send_email = false;
                $orderState->color = $os['color'];
                $orderState->hidden = false;
                $orderState->delivery = false;
                $orderState->logable = true;
                $orderState->invoice = false;
                $orderState->unremovable = false;
                if ($orderState->add()) {
                    Configuration::updateValue($os['key'], $orderState->id);
                }
                else
                    return false;
            }
        }
        return true;
    }

    /**
     * Registers module Admin tabs (controllers)
     */
    private function registerTabs()
    {
        $tabs = $this->getModuleTabs();

        if (empty($tabs)) {
            return true; // Nothing to register
        }

        foreach ($tabs as $controller => $tabData) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $controller;
            $tab->name = array();
            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                $tab->name[$language['id_lang']] = $tabData['title'];
            }

            $tab->id_parent = $tabData['parent_tab'];
            $tab->module = $this->name;
            if (!$tab->save()) {
                $this->displayError($this->l('Error while creating tab ') . $tabData['title']);
                return false;
            }
        }
        return true;
    }

    private function deleteTabs()
    {
        $tabs = $this->getModuleTabs();

        if (empty($tabs)) {
            return true; // Nothing to remove
        }

        foreach (array_keys($tabs) as $controller) {
            $idTab = (int) Tab::getIdFromClassName($controller);
            $tab = new Tab((int) $idTab);

            if (!Validate::isLoadedObject($tab)) {
                continue; // Nothing to remove
            }

            if (!$tab->delete()) {
                $this->displayError($this->l('Error while uninstalling tab') . ' ' . $tab->name);
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes a shipping method
     */
    public function deleteCarrier($key)
    {
        $carrier = new Carrier((int) (Configuration::get($key)));
        if (!$carrier) {
            return true; // carrier doesnt exist, no further action needed
        }

        if (Configuration::get('PS_CARRIER_DEFAULT') == (int) $carrier->id) {
            $this->updateDefaultCarrier();
        }

        $carrier->active = 0;
        $carrier->deleted = 1;

        if (!$carrier->update()) {
            return false;
        }

        return true;
    }

     /**
     * Change default carrier when deleting carrier
     */
    private function updateDefaultCarrier()
    {
        $carriers = $this->getAllCarriers();
        foreach ($carriers as $carrier) {
            if ($carrier['external_module_name'] != $this->name && $carrier['active'] && !$carrier['deleted']) {
                Configuration::updateValue('PS_CARRIER_DEFAULT', $carrier['id_carrier']);
                break;
            }
        }
    }

    /**
     * Get list of all Prestashop carriers
     */
    private function getAllCarriers($id_only = false)
    {
        $carriers = Carrier::getCarriers(
            Context::getContext()->language->id,
            true,
            false,
            false,
            NULL,
            PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
        );
        if ($id_only) {
            $id_list = array();
            foreach ($carriers as $carrier)
                $id_list[] = $carrier['id_carrier'];
            return $id_list;
        }

        return $carriers;
    }

    /**
     * Hook to display block in Prestashop order edit
     */
    public function hookDisplayAdminOrder($params)
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);

        if(Validate::isLoadedObject($order))
        {
            $orderCarrier = new Carrier($order->id_carrier);

            if($orderCarrier->external_module_name == $this->name)
            {
                $hrxOrder = new HrxOrder($id_order);

                if($hrxOrder->id_hrx && $hrxOrder->tracking_number == ''){
                    $result = HrxAPIHelper::getOrder($hrxOrder->id_hrx);
                    if(isset($result['tracking_number']) && isset($result['tracking_number'])){
                        $hrxOrder->tracking_number = $result['tracking_number'];
                        $hrxOrder->tracking_url = $result['tracking_url'];
                        $hrxOrder->update();
                    }
                }

                $this->context->smarty->assign([
                    'admin_default_tpl_path' => _PS_BO_ALL_THEMES_DIR_ . 'default/template/',
                    'images_url' => $this->_path . 'views/img/',
                ]);
                
                $address = new Address($order->id_address_delivery);
                $country_code = Country::getIsoById($address->id_country);

                $selected_terminal = HrxData::getDeliveryLocationInfo($hrxOrder->delivery_location_id, $country_code);

                $terminalsByDimensionsAndCity = [];
                if($hrxOrder->kind == self::$_carriers[self::CARRIER_TYPE_PICKUP]['kind']){
                    $terminalsByDimensionsAndCity = HrxData::getTerminalsByDimensionsAndCity($country_code, $hrxOrder, $selected_terminal);
                }

                $warehouses = HrxWarehouse::getWarehouses();

                $section_id = 'DELIVERY';
                $dimensions_fields = array(
                    array(
                        'name' => self::$_configKeys[$section_id]['w'],
                        'label' => $this->l('Width'),
                        'value' => $hrxOrder->width,
                        'unit' => 'cm'
                    ),
                    array(
                        'name' => self::$_configKeys[$section_id]['h'],
                        'label' => $this->l('Height'),
                        'value' => $hrxOrder->height,
                        'unit' => 'cm'
                    ),
                    array(
                        'name' => self::$_configKeys[$section_id]['l'],
                        'label' => $this->l('Length'),
                        'value' => $hrxOrder->length,
                        'unit' => 'cm'
                    ),
                );

                $weight = array(
                    'name' => self::$_configKeys[$section_id]['weight'],
                    'label' => $this->l('Weight'),
                    'value' => $hrxOrder->weight,
                    'unit' => 'kg'
                );
                
                $require_return_label = Configuration::get(self::$_configKeys['ADVANCED']['return_label']);

                $action_buttons = $this->getActionButtons($id_order, false);

                $this->context->smarty->assign([
                    'image' => __PS_BASE_URI__ . 'modules/' . $this->name .'/logo.png',
                    'dimensions_fields' => $dimensions_fields,
                    'weight' => $weight,
                    'terminals' => $terminalsByDimensionsAndCity,
                    'warehouses' => $warehouses,
                    'selected_terminal' => $selected_terminal,
                    'selected_warehouse' => $hrxOrder->pickup_location_id,
                    'id_order' => $id_order,
                    'status' => $hrxOrder->status_code,
                    'tracking' => [
                        'number' => $hrxOrder->tracking_number,
                        'url' => $hrxOrder->tracking_url,
                    ],
                    'require_return_label' => $require_return_label,
                    'actions' => $action_buttons,
                    'kind' => $hrxOrder->kind,
                ]);
                
                if(version_compare(_PS_VERSION_, '1.7', '>'))
                    return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name .'/views/templates/admin/displayAdminOrder.tpl');
                else
                    return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name .'/views/templates/admin/displayAdminOrder16.tpl');
            }
        }
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cart = $params['cart'];
        $carrier = new Carrier($order->id_carrier);

        if($carrier->external_module_name == $this->name)
        {
            $address = new Address($params['cart']->id_address_delivery);
            $country_code = Country::getIsoById($address->id_country);

            $order_weight = $order->getTotalWeight();

            //Convert to kg, if weight is in grams.
            if(Configuration::get('PS_WEIGHT_UNIT') == 'g')
                $order_weight *= 0.001;

            //Use default weight is product weigth was not setted
            if($order_weight == 0)
                $order_weight = Configuration::get(self::$_configKeys['DELIVERY']['weight']);

            $cart_products = $cart->getProducts();

            $package_dimensions = $this->getPackageDimensions($cart_products);
            
            $hrxOrder = new HrxOrder();
            $hrxOrder->force_id = true;
            $hrxOrder->id = $order->id;
            $hrxOrder->id_shop = $order->id_shop; 
            $hrxOrder->weight = $order_weight;
            $hrxOrder->length = $package_dimensions['l'];
            $hrxOrder->width = $package_dimensions['w'];
            $hrxOrder->height = $package_dimensions['h'];

            $carrier_id_reference = $carrier->id_reference;
            $carrier_type = $this->getCarrierType($carrier_id_reference);

            $default_warehouse = HrxWarehouse::getDefaultWarehouseId();
            if($default_warehouse){
                $hrxOrder->pickup_location_id = $default_warehouse;
            }

            if($carrier_type == self::CARRIER_TYPE_PICKUP)
            {
                $hrxOrder->kind = self::$_carriers[self::CARRIER_TYPE_PICKUP]['kind'];

                $terminal_id = HrxCartTerminal::getTerminalIdByCart($cart->id);
            
                if($terminal_id)
                {
                    $terminal_info = HrxData::getDeliveryLocationInfo($terminal_id, $country_code);
                    $hrxOrder->delivery_location_id = $terminal_id;
                    $hrxOrder->terminal = $terminal_info['address'] . ', ' . $terminal_info['city'] . ', ' . $terminal_info['country'];
                }
            }
            else
            {
                $hrxOrder->kind = self::$_carriers[self::CARRIER_TYPE_COURIER]['kind'];
            }

            $hrxOrder->add();
        }
    }

    /**
     * Hook to display content on carrier in checkout page
     */
    public function hookDisplayCarrierExtraContent($params)
    {
        $carrier_id_reference = $params['carrier']['id_reference'];
        
        $carrier_type = $this->getCarrierType($carrier_id_reference);

        $address = new Address($params['cart']->id_address_delivery);
        $country_code = Country::getIsoById($address->id_country);

        if (empty($country_code)) {
            return '';
        }

        if($carrier_type == 'courier')
        {
            return;
        }

        $terminals = HrxData::getTerminalsByCountry($country_code);
 
        if (!$terminals || empty($terminals)) {
            return '';
        }
        $selected_terminal_id = HrxCartTerminal::getTerminalIdByCart($params['cart']->id);

        $this->context->smarty->assign(
            array(
                'postcode' => $address->postcode,
                'city' => $address->city,
                'country_code' => $country_code,
                'selected_terminal' => $selected_terminal_id,
                'images_url' => $this->_path . 'views/img/',
            )
        );
        
        return $this->display(__FILE__, 'displayCarrierExtraContent.tpl');
        
    }

    private function getCarrierType($carrier_id_reference)
    {
        foreach(self::$_carriers as $carrier)
        {
            if((int)Configuration::get($carrier['reference_name']) == (int) $carrier_id_reference){
                return $carrier['type'];
            }
        }
    }

    public function hookDisplayBeforeCarrier($params)
    {
        if(version_compare(_PS_VERSION_, '1.7', '<'))
        {
            return $this->hookDisplayCarrierExtraContent($params);
        }
    }

    private function getPackageDimensions($products)
    {
        $dimensions = [
            'l' => [],
            'w' => [],
            'h' => []
        ];

        foreach ($products as $product)
        {
            $amount = (int) $product['cart_quantity'];

            for($i = 0; $i < $amount; $i++)
            {                    
                $dimensions['l'][] = $product['depth'] ? $product['depth'] : 1;
                $dimensions['w'][] = $product['width'] ? $product['width'] : 1;
                $dimensions['h'][] = $product['height'] ? $product['height'] : 1;
            }
        }

        $sum = [];
        $max = [];

        foreach($dimensions as $key => $dimension)
        {
            $sum[$key] = array_sum($dimension);
            $max[$key] = max($dimension);
        }

        $volumes = [];
         
        $volumes['by_x']['v'] = $sum['l'] * $max['w'] * $max['h'];
        $volumes['by_x']['d'] = ['w' => $max['w'], 'h' => $max['h'], 'l' => $sum['l']];

        $volumes['by_y']['v'] = $sum['h'] * $max['w'] * $max['l'];
        $volumes['by_y']['d'] = ['w' => $max['w'], 'h' => $sum['h'], 'l' =>$max['l']];

        $volumes['by_z']['v'] = $sum['w'] * $max['l'] * $max['h'];
        $volumes['by_z']['d'] = ['w' => $sum['w'], 'h' => $max['h'], 'l' => $max['l']];

        $smallest_volume_key = 'by_x';

        foreach($volumes as $key => $volume)
        {
            if($volume['v'] < $volumes[$smallest_volume_key]['v'])
            {
                $smallest_volume_key = $key;
            }
        }

        if($volumes[$smallest_volume_key]['v'] == 0)
        {
            return $this->getDefaultPackageDimensions();
        }
        
        if(Configuration::get('PS_DIMENSION_UNIT') == 'm')
        {
            foreach($volumes[$smallest_volume_key]['d'] as &$value)
            {
                $value = (int)$value * 100;
            }
        }

        return $volumes[$smallest_volume_key]['d'];
    }

    private function getDefaultPackageDimensions()
    {
        return [
            'w' => Configuration::get(self::$_configKeys['DELIVERY']['w']), 
            'h' => Configuration::get(self::$_configKeys['DELIVERY']['h']), 
            'l' => Configuration::get(self::$_configKeys['DELIVERY']['l'])
        ];
    }
    
    // Separate method, as methods of gettign a checkout step on 1.7 are inconsistent among minor versions.
    public function check17PaymentStep($cart)
    {
        if(version_compare(_PS_VERSION_, '1.7', '>'))
        {
            $rawData = Db::getInstance()->getValue(
                'SELECT checkout_session_data FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart = ' . (int) $cart->id
            );
            $data = json_decode($rawData, true);
            if (!is_array($data)) {
                $data = [];
            }
            // Do not add this module extra content, if it is payment step to avoid conflicts with venipakcod.
            if((isset($data['checkout-delivery-step']) && $data['checkout-delivery-step']['step_is_complete']) &&
                (isset($data['checkout-payment-step']) && !$data['checkout-payment-step']['step_is_complete'])
            )
            {
                return true;
            }
        }
        return false;
    }

    public function getActionButtons($id_order, $is_table = true)
    {
        $hrxOrder = new HrxOrder($id_order);
        $status = $hrxOrder->status_code;
        $kind = $hrxOrder->kind;

        $or_pickup = false;
        if($kind == self::$_carriers[self::CARRIER_TYPE_PICKUP]['kind']){
            $or_pickup = true;
        }

        $require_return_label = Configuration::get(HrxDelivery::$_configKeys['ADVANCED']['return_label']);

        $this->context->smarty->assign([
            'id_order' => $id_order,
            'icon' => 'icon-edit',
            'title' => $this->l('Edit'),
            'class' => 'change-order-modal',
            'href' => '#',
            'status' => $status,
            'require_return_label' => $require_return_label,
            'is_table' => $is_table,
            'or_pickup' => $or_pickup,
        ]);

        return $this->context->smarty->fetch(HrxDelivery::$_moduleDir . 'views/templates/admin/action_button.tpl');
    }
}
