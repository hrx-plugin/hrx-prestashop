<?php

class AdminHrxDeliveryCourierController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'hrx_delivery_courier';
        $this->identifier = 'id';
        $this->bulk_actions = [];
        $this->actions = [];
        $this->bootstrap = true;
        $this->list_no_link = true;

        parent::__construct();

        $this->toolbar_title = $this->module->l('HRX Courier Countries');

        $this->_select = ' a.active as country_active, cl.name as country_name';
        $this->_join = '
            LEFT JOIN ' . _DB_PREFIX_ . 'country c ON c.iso_code = a.country 
            LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON cl.id_country = c.id_country AND cl.id_lang = ' . (int) $this->context->language->id . ' 
        ';

        $this->prepareHrxList();
    }

    protected function prepareHrxList()
    {        
        $this->fields_list = array(
            'country_name' => array(
                'type' => 'text',
                'title' => $this->module->l('Country'),
                'align' => 'center',
                'filter_key' => 'cl!name',
            ),
            'country' => array(
                'type' => 'text',
                'title' => $this->module->l('ISO code'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'params' => array(
                'type' => 'text',
                'title' => $this->module->l('Limits'),
                'align' => 'center',
                'search' => false,
                'orderby' => false,
                'callback' => 'parseParams'
            ),
            'country_active' => array(
                'type' => 'bool',
                'active' => 'active',
                'title' => $this->module->l('Active'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ), 
        );
    }

    public function displayEnableLink($token, $id, $value, $active, $id_category = null, $id_product = null, $ajax = false)
    {
        $css_class = 'action-disabled';
        $check_html = '<i class="icon-remove"></i>';

        if ((bool) $value) {
            $css_class = 'action-enabled';
            $check_html = '<i class="icon-check"></i>';
        }

        return '<div class="list-action-enable ' . $css_class . '">' . $check_html . '</div>';
    }

    public function initToolbar()
    {
        $this->toolbar_btn = [];
    }

    public function parseParams($value)
    {
        $params = json_decode($value, true);
        return 'Min (cm): ' . $this->getMinDimensions($params) . 
            '<br>Max (cm): ' . $this->getMaxDimensions($params) . 
            '<br>Weight (kg): ' . $this->getMinWeight($params) .
            ' - ' . $this->getMaxWeight($params);
    }

    public function getMinDimensions($params, $formated = true)
    {
        $length = (float) (isset($params['min_length_cm']) ? $params['min_length_cm'] : 0);
        $width = (float) (isset($params['min_width_cm']) ? $params['min_width_cm'] : 0);
        $height = (float) (isset($params['min_height_cm']) ? $params['min_height_cm'] : 0);

        return $formated ? "$length x $width x $height" : [
            $length,
            $width,
            $height
        ];
    }

    public function getMaxDimensions($params, $formated = true)
    {
        $length = (float) (isset($params['max_length_cm']) ? $params['max_length_cm'] : 0);
        $width = (float) (isset($params['max_width_cm']) ? $params['max_width_cm'] : 0);
        $height = (float) (isset($params['max_height_cm']) ? $params['max_height_cm'] : 0);

        return $formated ? "$length x $width x $height" : [
            $length,
            $width,
            $height
        ];
    }

    public function getMinWeight($params)
    {
        return (float) (isset($params['min_weight_kg']) ? $params['min_weight_kg'] : 0);
    }

    public function getMaxWeight($params)
    {
        return (float) (isset($params['max_weight_kg']) ? $params['max_weight_kg'] : 0);
    }

}