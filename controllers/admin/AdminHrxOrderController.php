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
        $this->helper = $this;
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
            'printShipmentLabels' => array(
                'text' => $this->module->l('Print shipment label'),
                'icon' => 'icon-print'
            ),
            'printReturnLabels' => array(
                'text' => $this->module->l('Print return label'),
                'icon' => 'icon-print'
            ),
            'makeOrdersReady' => array(
                'text' => $this->module->l('Mark as ready'),
                'icon' => 'icon-save'
            )
        );
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

    public function postProcess()
    {
        parent::postProcess();
        $result = [];

        if(Tools::isSubmit('submitBulkprintShipmentLabelshrx_order'))
        {
            $result = self::printLabels('shipment');
        }

        if(Tools::isSubmit('submitBulkprintReturnLabelshrx_order'))
        {
            $result = self::printLabels('return');
        }

        if(isset($result['path']) && isset($result['filename']))
        {
            self::printPdf($result['path'], $result['filename']);
        }
    }

    private static function printLabels($label_type)
    {
        $result = [];
        $orders = Tools::getValue('hrx_orderBox');

        if(!$orders){
            return [];
        }

        $order_ids = implode(', ', $orders);
        $ids = HrxOrder::getHrxIds($order_ids);

        if($ids)
        {
            $file_name = $label_type . '_' . $order_ids . '.pdf';

            if($label_type == 'shipment')
                $label_directory = HrxDelivery::$_labelPdfDir;
            else {
                $label_directory = HrxDelivery::$_returnLabelPdfDir;
            }

            $file_path =  _PS_MODULE_DIR_ . $label_directory . $file_name;
            file_put_contents($file_path, '');

            foreach($ids as $id)
            {
                $response = HrxAPIHelper::getLabel($label_type, $id['id_hrx']);

                if(isset($response['error']))
                {
                    $result['errors'] = $response['error'];
                }
                else
                {
                    $file_content = $response['file_content'] ?? '';
                    file_put_contents($file_path, base64_decode($file_content), FILE_APPEND);
                    
                }
            }
            $result['path'] = $file_path;
            $result['filename'] = $file_name;
        }

        return $result;
    }

    private static function printPdf($path, $filename)
    {
        // // make sure there is nothing before headers
        if (ob_get_level()) ob_end_clean();
        header("Content-Type: application/pdf; name=\" " . $filename . ".pdf");
        //header("Content-Transfer-Encoding: base64");
        // disable caching on client and proxies, if the download content vary
        header("Expires: 0");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        readfile($path);
    }
}