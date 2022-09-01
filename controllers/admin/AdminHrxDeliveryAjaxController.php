<?php

require_once "AdminHrxOrderController.php";

class AdminHrxDeliveryAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        if (!Context::getContext()->employee->isLoggedBack()) {
            exit('Restricted.');
        }

        parent::__construct();
        $this->parseActions();
    }

    private function parseActions()
    {
        $action = Tools::getValue('action');
        switch ($action) {
            case 'prepareModal':
                $this->prepareOrderModal();
                break;
            case 'saveOrder':
                $this->saveOrder();
                break;
            case 'createOrder':
                $this->createOrder();
                break;
            case 'updateReadyState':
                $this->updateReadyState();
                break;
            case 'cancelOrder':
                $this->cancelOrder();
                break;
            case 'printLabel':
                $this->printLabel();
                break;
            case 'updateTerminalList':
                $this->getTerminalList();
                break;
            case 'updateTerminals':
                $this->updateTerminals();
                break;
            case 'updatePriceTable':
                $this->updatePriceTable();
                break;
        }
    }

    private function updateReadyState()
    {
        $result = [];
        $id_order = Tools::getValue('id_order');
        $is_table = Tools::getValue('is_table');
        $hrxOrder = new HrxOrder($id_order);

        if(Validate::isLoadedObject($hrxOrder))
        {
            $response = HrxAPIHelper::updateReadyState($hrxOrder->id_hrx);
            $response['success'] = 'true';
            if(isset($response['error'])){
                $result['errors'][] = $response['error'];
            }      
            else{
                $hrxOrder->status = Configuration::get(HrxDelivery::$_order_states['ready']['key']);
                $hrxOrder->status_code = 'ready';
                $hrxOrder->update();

                $this->changeOrderStatus($id_order, Configuration::get(HrxDelivery::$_order_states['ready']['key']));

                $actions = $this->module->getActionButtons($hrxOrder->id, $is_table);

                $result['success'][] = $this->module->l('Order status changed to ready successfully.');
                $result['data']['status'] = $this->getStatusHtml(HrxDelivery::$_order_states['ready']);
                $result['actions'] = $actions;
            }      
        }
        die(json_encode($result));
    }

    protected function saveOrder()
    {
        $id_order = (int) Tools::getValue('id_order');
        $pickup_location_id = Tools::getValue('pickup_location_id');
        $delivery_location_id = Tools::getValue('delivery_location_id');

        $shipmentData = new stdClass();
        $shipmentData->length = (float) Tools::getValue('HRX_DEFAULT_LENGTH');
        $shipmentData->width = (float) Tools::getValue('HRX_DEFAULT_WIDTH');
        $shipmentData->height = (float) Tools::getValue('HRX_DEFAULT_HEIGHT');
        $shipmentData->weight = (float) Tools::getValue('HRX_DEFAULT_WEIGHT');

        $order = new Order($id_order);
        $address = new Address($order->id_address_delivery);
        $country_code = Country::getIsoById($address->id_country);
        $terminal_info = [];

        $kind = Tools::getValue('kind');

        if($kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']){

            if(!$delivery_location_id)
            {
                $result['errors'][] = $this->module->l('Please select a terminal.');
            }
            else
            {
                $terminal_info = HrxData::getDeliveryLocationInfo($delivery_location_id, $country_code);

                if(!HrxData::isFit($terminal_info, $shipmentData))
                {
                    $result['errors'][] = $this->module->l('The parcel does not fit into the parcel terminal. Update the list of terminals and select another terminal.');
                }
            }            
        }
        
        if(!$pickup_location_id)
        {
            $result['errors'][] = $this->module->l('Please select a warehouse.');
        }

        if(!$shipmentData->length || !$shipmentData->width || !$shipmentData->height || !$shipmentData->weight)
        {
            $result['errors'][] = $this->module->l('Parcel dimensions and weight are required.');
        }
        
        if(!isset($result['errors']))
        {
            $obj = new HrxOrder($id_order);
            $obj->id_shop = $this->context->shop->id;
            $obj->pickup_location_id = $pickup_location_id;
            $obj->delivery_location_id = $delivery_location_id;

            if($kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind'])
                $obj->terminal = $terminal_info['address'] . ', ' . $terminal_info['city'] . ', ' . $terminal_info['country'];
            else
                $obj->terminal = '--';

            $obj->length = $shipmentData->length;
            $obj->width = $shipmentData->width;
            $obj->height = $shipmentData->height;
            $obj->weight = $shipmentData->weight;
    
            $res = $obj->update();
    
            if($res){
                $result['success'][] = $this->module->l('Shipment data updated successfully.');
                $result['data']['terminal'] = $obj->terminal;
                $result['data']['warehouse'] = HrxWarehouse::getName($pickup_location_id);
            }
            else{
                $result['errors'][] = $this->module->l('Failed to update shipment data.');
            }
        }

        die(json_encode($result));
    }

    public function getTerminalList()
    {
        $id_order = (int) Tools::getValue('id_order');
        $order = new Order($id_order);

        $address = new Address($order->id_address_delivery);
        $country_code = Country::getIsoById($address->id_country);
        $hrxOrder = new HrxOrder($id_order);

        $shipmentData = new stdClass();
        $shipmentData->length = (float) Tools::getValue('HRX_DEFAULT_LENGTH');
        $shipmentData->width = (float) Tools::getValue('HRX_DEFAULT_WIDTH');
        $shipmentData->height = (float) Tools::getValue('HRX_DEFAULT_HEIGHT');
        $shipmentData->weight = (float) Tools::getValue('HRX_DEFAULT_WEIGHT');

        $selected_terminal = HrxData::getDeliveryLocationInfo($hrxOrder->delivery_location_id, $country_code);

        $terminals = [];
        if($hrxOrder->kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']){
            $terminals = HrxData::getTerminalsByDimensionsAndCity($country_code, $shipmentData, $selected_terminal);
        }
        
        $html = '';

        if($terminals)
        {
            $selected_terminal = $hrxOrder->delivery_location_id;
            $html = self::generateTerminalList($terminals, $selected_terminal);
        }else{
            $html = ' <div class="alert alert-warning" role="alert">'. $this->module->l('There are no terminals for the specified shipment sizes') .'</div>';
        }
        die(json_encode(['terminals' => $html]));
    }

    private static function generateTerminalList($terminals, $selected_terminal)
    {
        $html = '<select name="delivery_location_id" id="delivery_location_id" class="custom-select">';
        
        foreach($terminals as $terminal)
        {
            if($terminal['id'] == $selected_terminal){
                $html .= '<option value="' . $terminal['id'] . '" selected>';
            }else{
                $html .= '<option value="' . $terminal['id'] . '">';
            }

            $html .= $terminal['address'] . ', ' . $terminal['city'] . ', ' . $terminal['country'];
            $html .= '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    public function printLabel()
    {
        $result = [];
        $id_order = Tools::getValue('id_order');
        $label_type = Tools::getValue('type');
        $default_file_name = $label_type . '_' . $id_order . '.pdf';

        if($label_type == 'shipment')
            $label_directory = HrxDelivery::$_labelPdfDir;
        else 
            $label_directory = HrxDelivery::$_returnLabelPdfDir;

        $hrxOrder = new HrxOrder($id_order);
        if(Validate::isLoadedObject($hrxOrder))
        {
            $response = HrxAPIHelper::getLabel($label_type, $hrxOrder->id_hrx);

            if(isset($response['error']))
            {
                $result['errors'] = $response['error'];
            }
            else
            {
                $file_content = $response['file_content'] ?? '';
                $file_name = $response['file_name'] ?? $default_file_name;
                $file_path =  _PS_MODULE_DIR_ . $label_directory . $file_name;
                //$data = base64_encode($file_content);
                file_put_contents($file_path, base64_decode($file_content));
                $result['success'][] = 'Order shipping label generated successfully';
                $result['data']['url'] = _PS_BASE_URL_ . '/modules/' . $label_directory . $file_name;
            }
        }

        die(json_encode($result));
    }

    public function prepareOrderModal()
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);

        if(Validate::isLoadedObject($order))
        {            
            $hrxOrder = new HrxOrder($id_order);
            
            $address = new Address($order->id_address_delivery);
            $country_code = Country::getIsoById($address->id_country);
            $selected_terminal = HrxData::getDeliveryLocationInfo($hrxOrder->delivery_location_id, $country_code);

            $terminalsByCities = [];
            if($hrxOrder->kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']){
                $terminalsByCities = HrxData::getTerminalsByDimensionsAndCity($country_code, $hrxOrder, $selected_terminal);
            }

            $warehouses = HrxWarehouse::getWarehouses();

            $section_id = 'DELIVERY';
            $dimensions_fields = array(
                array(
                    'name' => HrxDelivery::$_configKeys[$section_id]['w'],
                    'label' => $this->l('Width'),
                    'value' => $hrxOrder->width,
                    'unit' => 'cm'
                ),
                array(
                    'name' => HrxDelivery::$_configKeys[$section_id]['h'],
                    'label' => $this->l('Height'),
                    'value' => $hrxOrder->height,
                    'unit' => 'cm'
                ),
                array(
                    'name' => HrxDelivery::$_configKeys[$section_id]['l'],
                    'label' => $this->l('Length'),
                    'value' => $hrxOrder->length,
                    'unit' => 'cm'
                ),
            );

            $weight = array(
                'name' => HrxDelivery::$_configKeys[$section_id]['weight'],
                'label' => $this->l('Weight'),
                'value' => $hrxOrder->weight,
                'unit' => 'kg'
            );

            $actions = $this->module->getActionButtons($hrxOrder->id, false);

            $require_return_label = Configuration::get(HrxDelivery::$_configKeys['ADVANCED']['return_label']);

            $this->context->smarty->assign([
                'image' => __PS_BASE_URI__ . 'modules/hrxdelivery/logo.png',
                'dimensions_fields' => $dimensions_fields,
                'weight' => $weight,
                'terminals' => $terminalsByCities,
                'warehouses' => $warehouses,
                'selected_terminal' => $selected_terminal,
                'selected_warehouse' => $hrxOrder->pickup_location_id,
                'id_order' => $id_order,
                'status' => $hrxOrder->status_code,
                'require_return_label' => $require_return_label,
                'actions' => $actions,
                'kind' => $hrxOrder->kind,
                'select_warehouse' => $this->l('Please select warehouse'),
            ]);

            die(json_encode(['modal' => $this->context->smarty->fetch(HrxDelivery::$_moduleDir . 'views/templates/admin/order_modal.tpl')]));
        }
    }

    public function createOrder()
    {
        $id_order = Tools::getValue('id_order');
        $is_table = Tools::getValue('is_table');
        $order = new Order($id_order);
        $result = [];

        if(Validate::isLoadedObject($order))
        {            
            $hrxOrder = new HrxOrder($id_order);
            
            $address = new Address($order->id_address_delivery);
            $customer = new Customer($order->id_customer);
            $country_code = Country::getIsoById($address->id_country);
            $message = $order->getFirstMessage() ?? '';

            $pickup_location_id = $hrxOrder->pickup_location_id;
            if(!$pickup_location_id){
                $result['errors'][] = $this->module->l('Warehouse is required.');
            }

            if($hrxOrder->kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind'])
            {
                $delivery_location = HrxData::getDeliveryLocationInfo($hrxOrder->delivery_location_id, $country_code);
                if(!$delivery_location){
                    $result['errors'][] = $this->module->l('Parcel terminal is required.');
                }
            }
            else
            {
                $delivery_location = HrxData::getCourierDeliveryLocation($country_code);
            }
            
            $phone_patern = $delivery_location['recipient_phone_regexp'] ?? '';
            $phone_prefix = $delivery_location['recipient_phone_prefix'] ?? '';
            $phone = self::preparePhoneNumber($address->phone, $phone_prefix, $phone_patern);
            if(!$phone){
                $result['errors'][] = $this->module->l('Invalid phone format.');
            }

            $customerData = [
                'name' => $address->firstname . ' ' . $address->lastname,
                'email' => $customer->email,
                'phone' => $phone,
                'postcode' => $address->postcode,
                'city' => $address->city,
                'country' => $country_code,
                'address' => $address->address1,
            ];

            $shipmentData = [
                'reference' => 'PACK-' . $order->reference,
                'comment' => $message,
                'l' => $hrxOrder->length,
                'w' => $hrxOrder->width,
                'h' => $hrxOrder->height,
                'weight' => $hrxOrder->weight,
            ];

            if(isset($result['errors'])){
                die(json_encode($result));
            }

            $delivery_kind = $hrxOrder->kind;

            $response = HrxAPIHelper::createOrder($pickup_location_id, $delivery_location, $customerData, $shipmentData, $delivery_kind);

            if(isset($response['success'])){
                $res = $response['success'];
                $hrxOrder->id_hrx = $res['id'] ?? '';
                $hrxOrder->tracking_number = $res['tracking_number'] ?? '';
                $hrxOrder->tracking_url = $res['tracking_url'] ?? '';
                $hrxOrder->status = Configuration::get(HrxDelivery::$_order_states['new']['key']);
                $hrxOrder->status_code = 'new';

                $this->changeOrderStatus($id_order, Configuration::get(HrxDelivery::$_order_states['new']['key']));

                $hrxOrder->update();

                $actions = $this->module->getActionButtons($hrxOrder->id, $is_table);

                $result['success'][] = $this->module->l('Order created successfully.');
                $result['data']['tracking_number'] = $res['tracking_number'] ?? '';
                $result['data']['status'] = $this->getStatusHtml(HrxDelivery::$_order_states['new']);
                $result['actions'] = $actions;
            }else{
                $result['errors'] = $response['error'];
            }
        }
        die(json_encode($result));
    }

    private function changeOrderStatus($id_order, $status)
    {
        $order = new Order((int)$id_order);
        if ($order->current_state != $status)
        {
            $history = new OrderHistory();
            $history->id_order = (int)$id_order;
            $history->id_employee = Context::getContext()->employee->id;
            $history->changeIdOrderState((int)$status, $order);
            $order->update();
            $history->add();
        }
    }

    private function cancelOrder()
    {
        $result = [];
        $id_order = Tools::getValue('id_order');
        $hrxOrder = new HrxOrder($id_order);

        if(Validate::isLoadedObject($hrxOrder))
        {
            $response = HrxAPIHelper::cancelOrder($hrxOrder->id_hrx);
            if(isset($response['error'])){
                $result['errors'][] = $response['error'];
            }      
            else{
                $hrxOrder->status = Configuration::get(HrxDelivery::$_order_states['cancelled']['key']);
                $hrxOrder->status_code = 'cancelled';
                $hrxOrder->update();

                $this->changeOrderStatus($id_order, Configuration::get(HrxDelivery::$_order_states['cancelled']['key']));

                $result['success'][] = $this->module->l('Order canceled successfully.');
                $result['data']['status'] = $this->getStatusHtml(HrxDelivery::$_order_states['cancelled']);
            }      
        }
        die(json_encode($result)); 
    }

    private static function preparePhoneNumber($phone, $phone_prefix, $patern)
    {
        
        if(!preg_match('/'.$patern.'/', $phone))
        {
            $phone = str_replace($phone_prefix, '', $phone);
        }
        return $phone;
    }

    private static function getStatusHtml($status)
    {
        $iso_code = Context::getContext()->language->iso_code == 'lt' ? 'lt' : 'en';
        $html = '<span class="label color_field" style="background-color:' . $status['color'] . ';color:' . $status['text-color'] . ';border-color:' . $status['text-color'] . '">' . $status['lang'][$iso_code] . '</span>';
        return $html;
    }

    private function updateTerminals()
    {
        $page = 1;
        if(Tools::getIsset('page')){
            $page = Tools::getValue('page');
        }
        $counter = (int) (Tools::getValue('counter') ?? 0);
        $response = HrxData::updateTerminals($page);

        $result = [];

        if(isset($response['error'])){
            $result['error'] = $response['error'];
        }

        if(isset($response['counter']))
        {
            $result = [
                'url' => $this->context->link->getAdminLink(HrxDelivery::CONTROLLER_ADMIN_AJAX) . '&action=updateTerminals&page=' . ($page + 1) . '&counter=' . ($counter + $response['counter']),
                'counter' => ($counter + $response['counter']),
            ];
        }

        if(!$response)
        {
            $result['counter'] = $counter;
        }

        die(json_encode($result)); 
    }

    private function updatePriceTable()
    {
        $data = Tools::getValue('data');

        $result = HrxShippingPrice::updateTable($data);

        if($result){
            $res['success'] = $this->module->l('Shipping price table updated successfully.');
        }else{
            $res['error'] = $this->module->l('Something went wrong.');
        }

        die(json_encode($res)); 
    }
}