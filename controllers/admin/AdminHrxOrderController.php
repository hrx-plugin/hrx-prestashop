<?php

class AdminHrxOrderController extends ModuleAdminController
{
    public function __construct()
    {
        
        parent::__construct();

        $this->list_no_link = true;
        $this->bootstrap = true;
        $this->_orderBy = 'id';
        $this->_orderWay = 'desc';
        $this->table = 'hrx_order';
        $this->identifier = 'id';

        $this->_select = ' CONCAT(c.firstname, " ", c.lastname) AS customer_name,
                            osl.name AS order_state, s.name AS shop_name, osl.name AS osname, os.color,
                            w.name as warehouse, a.id AS id_order_1';

        $this->_join = '
        LEFT JOIN ' . _DB_PREFIX_ . 'hrx_warehouse w ON (w.id_warehouse = a.pickup_location_id)
        LEFT JOIN ' . _DB_PREFIX_ . 'shop s ON (s.id_shop = a.id_shop)
        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.id_order = a.id)
        LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (c.id_customer = o.id_customer)
        LEFT JOIN ' . _DB_PREFIX_ . 'order_state os ON (o.current_state = os.id_order_state)
        LEFT JOIN ' . _DB_PREFIX_ . 'order_state_lang osl ON (o.current_state = osl.id_order_state AND osl.id_lang = ' . (int) $this->context->language->id . ')';

        $this->toolbar_title = $this->module->l('HRX Orders');

        $statuses = OrderState::getOrderStates((int) $this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }


        $this->setTrackingNumbers();

        $this->prepareOrderList();
    }

    protected function prepareOrderList()
    {        
        $this->fields_list = array(
            'id' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'filter_key' => 'a!id',
                'class' => 'fixed-width-xs',
            ),
            'shop_name' => array(
                'title' => $this->module->l('Shop'),
                'align' => 'text-center',
                'filter_key' => 's!name',
                'class' => 'fixed-width-xs',
            ),
            'osname' => array(
                'title' => $this->module->l('Status'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname',
                'align' => 'center',
                'class' => 'column-osname'
            ),
            'customer_name' => array(
                'title' => $this->module->l('Customer'),
                'type' => 'text',
                'align' => 'center',
                'havingFilter' => true,
            ),
            'tracking_number' => array(
                'type' => 'text',
                'title' => $this->module->l('Tracking number'),
                'align' => 'center',
            ),
            'terminal' => array(
                'type' => 'text',
                'title' => $this->module->l('Parcel terminal'),
                'align' => 'center',
                'class' => 'column-terminal'
            ),
            'warehouse' => array(
                'type' => 'text',
                'title' => $this->module->l('Warehouse'),
                'align' => 'center',
                'class' => 'column-warehouse',
                'havingFilter' => true,
            ),  
        );

        $this->fields_list['id_order_1'] = array(
            'title' => 'Actions',
            'align' => 'center remove-dashes',
            'search' => false,
            'orderby' => false,
            'callback' => 'EditOrderBtn',
            'class' => 'id_order_1'
        );

        $this->bulk_actions = array(
            'printLabels' => array(
                'text' => $this->module->l('Print Labels'),
                'icon' => 'icon-print'
            ),
            'markAsReady' => array(
                'text' => $this->module->l('Mark as ready'),
                'icon' => 'icon-save'
            ),
        );
        $require_return_label = Configuration::get(HrxDelivery::$_configKeys['ADVANCED']['return_label']);
        if($require_return_label)
        {
            $this->bulk_actions['printReturnLabels'] = array(
                    'text' => $this->module->l('Print Return Labels'),
                    'icon' => 'icon-print'
            );
        }
    }

    public function EditOrderBtn($id_order)
    {
        return $this->module->getActionButtons($id_order);
    }

    public function initToolbar()
    {
        $this->toolbar_btn = [];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        Media::addJsDef([
            'hrx_delivery_order_modal_url' => $this->context->link->getAdminLink(HrxDelivery::CONTROLLER_ADMIN_AJAX) . '&action=prepareModal',
            'hrxdelivery_create_order_url' => $this->context->link->getAdminLink(HrxDelivery::CONTROLLER_ADMIN_AJAX) . '&action=createOrder',
            'hrxdelivery_update_terminal_list' => $this->context->link->getAdminLink(HrxDelivery::CONTROLLER_ADMIN_AJAX) . '&action=updateTerminalList',
            'hrxdelivery_print_label_url' => $this->context->link->getAdminLink(HrxDelivery::CONTROLLER_ADMIN_AJAX) . '&action=printLabel',
            'hrxdelivery_cancel_order_url' => $this->context->link->getAdminLink(HrxDelivery::CONTROLLER_ADMIN_AJAX) . '&action=cancelOrder',
            'hrxdelivery_update_ready_state' => $this->context->link->getAdminLink(HrxDelivery::CONTROLLER_ADMIN_AJAX) . '&action=updateReadyState',
        ]);
    }

    private function setTrackingNumbers()
    {
        $orders = HrxOrder::getOrdersWithoutTracking();


        if($orders)
        {
            foreach($orders as $order)
            {
                $hrxOrder = new HrxOrder((int)$order['id']);
                $result = HrxAPIHelper::getOrder($hrxOrder->id_hrx);

                if(isset($result['tracking_number']) && isset($result['tracking_number']))
                {
                    $hrxOrder->tracking_number = $result['tracking_number'];
                    $hrxOrder->tracking_url = $result['tracking_url'];
                    $hrxOrder->update();
                    $order->setWsShippingNumber($result['tracking_number']);
                    $order->update();
                }
            }
        }
    }

    public function postProcess()
    {
        parent::postProcess();
        if(Tools::isSubmit('submitBulkprintLabelshrx_order'))
        {
            $order_ids = Tools::getValue('hrx_orderBox');
            HrxData::bulkPrintLabels($order_ids, 'shipment');
        }

        if(Tools::isSubmit('submitBulkprintReturnLabelshrx_order'))
        {
            $order_ids = Tools::getValue('hrx_orderBox');
            HrxData::bulkPrintLabels($order_ids, 'return');
        }

        if(Tools::isSubmit('submitBulkmarkAsReadyhrx_order'))
        {
            $order_ids = Tools::getValue('hrx_orderBox');
            if($order_ids)
                $this->bulkUpdateReadyState($order_ids);
        }
        
    }

    private function bulkUpdateReadyState($order_ids)
    {
        foreach($order_ids as $id)
        {
            $hrxOrder = new HrxOrder($id);

            if(Validate::isLoadedObject($hrxOrder) && $hrxOrder->id_hrx != '')
            {
                $response = HrxAPIHelper::updateReadyState($hrxOrder->id_hrx);

                if(isset($response['error'])){
                    $result['errors'][] = $response['error'];
                }      
                else{
                    $new_status_id = Configuration::get(HrxDelivery::$_order_states['ready']['key']);
                    
                    $hrxOrder->status = $new_status_id;
                    $hrxOrder->status_code = 'ready';
                    $hrxOrder->update();
    
                    HrxDelivery::changeOrderStatus($id, $new_status_id);
                }
            }
        }
    }
}