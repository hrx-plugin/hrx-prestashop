<?php

class AdminHrxWarehouseController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'hrx_warehouse';
        $this->identifier = 'id';
        $this->bulk_actions = [];
        $this->actions = [];
        $this->bootstrap = true;
        $this->list_no_link = true;

        parent::__construct();

        $this->toolbar_title = $this->module->l('HRX Warehouses');
        $this->prepareWarehouseList();
    }

    protected function prepareWarehouseList()
    {        
        $this->fields_list = array(
            'id_warehouse' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
            ),
            'name' => array(
                'title' => $this->module->l('Title'),
                'align' => 'text-center',
            ),
            'country' => array(
                'type' => 'text',
                'title' => $this->module->l('Country code'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'city' => array(
                'title' => $this->module->l('City'),
                'type' => 'text',
                'align' => 'center',
            ),
            'zip' => array(
                'title' => $this->module->l('Post code'),
                'type' => 'text',
                'align' => 'center',
            ),
            'address' => array(
                'type' => 'text',
                'title' => $this->module->l('Address'),
                'align' => 'center',
            ),
            'default_warehouse' => array(
                'type' => 'bool',
                'active' => 'defaul_warehouse',
                'title' => $this->module->l('Default warehouse'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            
        );
    }

    public function initToolbar()
    {
        $this->toolbar_btn = [];
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['sync_warehouses'] = [
            'href' => self::$currentIndex . '&sync_warehouses=1&token=' . $this->token,
            'desc' => $this->module->l('Update Warehouses'),
            'imgclass' => 'refresh',
        ];
        
        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        parent::postProcess();

        if(Tools::isSubmit(('defaul_warehousehrx_warehouse')) && $id = Tools::getValue('id'))
        {
            HrxWarehouse::changeDefaultWarehouse($id);
        }
        
        if(Tools::getValue('sync_warehouses'))
        {
            $response = HrxData::updateWarehouses(true);

            if($response != true)
            {
                return $this->displayWarning($response);
            }
        }
    }

}