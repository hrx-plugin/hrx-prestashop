<?php

class AdminHrxDeliveryTerminalController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'hrx_delivery_terminal';
        $this->identifier = 'id';
        $this->bulk_actions = [];
        $this->actions = [];
        $this->bootstrap = true;
        $this->list_no_link = true;
        
        parent::__construct();

        $this->toolbar_title = $this->module->l('HRX Terminals');

        $this->_select = ' a.active as country_active';

        $this->prepareHrxList();
    }

    protected function prepareHrxList()
    {        
        $this->fields_list = array(
            'id_terminal' => array(
                'type' => 'text',
                'title' => $this->module->l('HRX ID'),
                'align' => 'center',
            ),
            'country' => array(
                'type' => 'text',
                'title' => $this->module->l('Country'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'address' => array(
                'type' => 'text',
                'title' => $this->module->l('Address'),
                'align' => 'center',
            ),
            'zip' => array(
                'type' => 'text',
                'title' => $this->module->l('ZIP'),
                'align' => 'center',
            ),
            'city' => array(
                'type' => 'text',
                'title' => $this->module->l('City'),
                'align' => 'center',
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
                'active' => 'country_active',
                'title' => $this->module->l('Active'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!active',
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