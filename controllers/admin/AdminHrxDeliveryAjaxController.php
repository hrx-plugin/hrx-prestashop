<?php

use Mijora\Hrx\DVDoug\BoxPacker\ItemList;
use Mijora\Hrx\DVDoug\BoxPacker\ParcelBox;
use Mijora\Hrx\DVDoug\BoxPacker\ParcelItem;

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
            case 'updateCourierLocations':
                $this->updateCourierLocations();
                break;
            case 'getAvailableCountries':
                $this->getAvailableCountries();
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
                $new_status_id = Configuration::get(HrxDelivery::$_order_states['ready']['key']);

                $hrxOrder->status = $new_status_id;
                $hrxOrder->status_code = 'ready';
                $hrxOrder->update();

                HrxDelivery::changeOrderStatus($id_order, $new_status_id);

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

        $kind = Tools::getValue('kind');

        if(!$shipmentData->length || !$shipmentData->width || !$shipmentData->height || !$shipmentData->weight) {
            $result['errors'][] = $this->module->l('Parcel dimensions and weight are required.');
        }

        if($kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']) {
            if(!$delivery_location_id) {
                $result['errors'][] = $this->module->l('Please select a terminal.');
            } else {
                $delivery_location = new HrxDeliveryTerminal($delivery_location_id);
            }            
        } else { // courier
            $delivery_location = HrxDeliveryCourier::getDeliveryCourierByCountry($country_code);
        }

        if (Validate::isLoadedObject($delivery_location)) {
            $item_list = new ItemList();
            $item_list->insert(
                new ParcelItem(
                    $shipmentData->length,
                    $shipmentData->width,
                    $shipmentData->height,
                    $shipmentData->weight,
                    'custom_parcel'
                ), 
                1
            );

            if (!HrxData::doesParcelFitBox($delivery_location->getParams(), $item_list)) {
                $result['errors'][] = $this->module->l('Parcel does not fit delivery locations limitations:') 
                    . ' MIN (cm): ' . HrxData::getMinDimensions($delivery_location->getParams(), true) . ', '
                    . ' MAX (cm): ' . HrxData::getMaxDimensions($delivery_location->getParams(), true) . ', '
                    . ' MIN (kg): ' . HrxData::getMinWeight($delivery_location->getParams(), true) . ', '
                    . ' MAX (kg): ' . HrxData::getMaxWeight($delivery_location->getParams(), true) . '.';
            }
        } else {
            $result['errors'][] = $this->module->l('There was an error trying to determina delivery location');
        }

        if(!$pickup_location_id) {
            $result['errors'][] = $this->module->l('Please select a warehouse.');
        }

        if(!isset($result['errors'])) {
            $obj = new HrxOrder($id_order);
            $obj->id_shop = $this->context->shop->id;
            $obj->pickup_location_id = $pickup_location_id;
            $obj->delivery_location_id = $delivery_location_id;

            if($kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind'])
                $obj->terminal = $delivery_location->address . ', ' . $delivery_location->city . ', ' . $delivery_location->country;
            else
                $obj->terminal = '--';

            $packed_box = HrxData::getPackedBox($delivery_location->getParams(), $item_list);

            $min_dimmensions = HrxData::getMinDimensions($delivery_location->getParams());

            $obj->length = max($packed_box->getUsedLength() / 10, $min_dimmensions[ParcelBox::DIMENSION_LENGTH]);
            $obj->width = max($packed_box->getUsedWidth() / 10, $min_dimmensions[ParcelBox::DIMENSION_WIDTH]);
            $obj->height = max($packed_box->getUsedDepth() / 10, $min_dimmensions[ParcelBox::DIMENSION_HEIGHT]);
            $obj->weight = max($packed_box->getWeight() / 1000, HrxData::getMinWeight($delivery_location->getParams()));

            $res = $obj->update();
    
            if ($res) {
                $result['success'][] = $this->module->l('Shipment data updated successfully. Parcel size addapted to location limits');
                $result['data']['terminal'] = $obj->terminal;
                $result['data']['warehouse'] = HrxWarehouse::getName($pickup_location_id);
                $result['data']['dimmensions'] = [
                    'HRX_DEFAULT_LENGTH' => $obj->length,
                    'HRX_DEFAULT_WIDTH' => $obj->width,
                    'HRX_DEFAULT_HEIGHT' => $obj->height,
                    'HRX_DEFAULT_WEIGHT' => $obj->weight,
                ];
            } else {
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

        $selected_terminal = null;
        
        $terminals = [];
        if($hrxOrder->kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']){
            $selected_terminal = new HrxDeliveryTerminal($hrxOrder->delivery_location_id);
            $terminals = HrxData::getTerminalsByDimensionsAndCity($country_code, $shipmentData, $selected_terminal);
        }
        
        $html = '';

        if($terminals)
        {
            $html = self::generateTerminalList($terminals, $hrxOrder->delivery_location_id);
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

        if ($label_type == 'shipment')
        $label_directory = HrxDelivery::$_labelPdfDir;
        else
            $label_directory = HrxDelivery::$_returnLabelPdfDir;

        $hrxOrder = new HrxOrder($id_order);

        if (Validate::isLoadedObject($hrxOrder)) {
            $response = HrxAPIHelper::getLabel($label_type, $hrxOrder->id_hrx);

            if (isset($response['error'])) {
                $result['errors'] = $response['error'];
            } else {
                $file_content = $response['file_content'] ?? '';
                $file_name = $response['file_name'] ?? $default_file_name;
                $result['success'][] = 'Order shipping label generated successfully';
                $result['data']['pdfBase64'] = $file_content;
                $result['data']['filename'] = $file_name;
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
            // $selected_terminal = HrxData::getDeliveryLocationInfo($hrxOrder->delivery_location_id, $country_code);
            
            $selected_terminal = null;
            $terminalsByCities = [];
            if($hrxOrder->kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']){
                $selected_terminal = new HrxDeliveryTerminal($hrxOrder->delivery_location_id);

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

            die(json_encode([
                'modal' => $this->context->smarty->fetch(HrxDelivery::$_moduleDir . 'views/templates/admin/order_modal.tpl')
            ]));
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

            if($hrxOrder->kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']) {
                // $delivery_location = HrxData::getDeliveryLocationInfo($hrxOrder->delivery_location_id, $country_code);
                $delivery_location = new HrxDeliveryTerminal($hrxOrder->delivery_location_id);
                if(!Validate::isLoadedObject($delivery_location)){
                    $result['errors'][] = $this->module->l('Parcel terminal is required.');
                }
            } else {
                // $delivery_location = HrxData::getCourierDeliveryLocation($country_code);
                $delivery_location = HrxDeliveryCourier::getDeliveryCourierByCountry($country_code);
            }
            
            $phone_patern = $delivery_location->getParams()['recipient_phone_regexp'] ?? '';
            $phone_prefix = $delivery_location->getParams()['recipient_phone_prefix'] ?? '';
            $phone = (!empty($address->phone_mobile)) ? $address->phone_mobile : $address->phone;
            $phone = self::preparePhoneNumber($phone, $phone_prefix, $phone_patern);
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

                $order->setWsShippingNumber($res['tracking_number'] ?? '');
                $order->update();

                $new_status_id = Configuration::get(HrxDelivery::$_order_states['new']['key']);

                $hrxOrder->id_hrx = $res['id'] ?? '';
                $hrxOrder->tracking_number = $res['tracking_number'] ?? '';
                $hrxOrder->tracking_url = $res['tracking_url'] ?? '';
                $hrxOrder->status = $new_status_id;
                $hrxOrder->status_code = 'new';
                $hrxOrder->update();

                HrxDelivery::changeOrderStatus($id_order, $new_status_id);

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
                $new_status_id = Configuration::get(HrxDelivery::$_order_states['cancelled']['key']);

                $hrxOrder->status = $new_status_id;
                $hrxOrder->status_code = 'cancelled';
                $hrxOrder->update();

                HrxDelivery::changeOrderStatus($id_order, $new_status_id);

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

    private function updateCourierLocations()
    {
        $page = 1;
        if(Tools::getIsset('page')){
            $page = Tools::getValue('page');
        }
        $counter = (int) (Tools::getValue('counter') ?? 0);
        $response = HrxData::updateCourierPoints($page);

        $result = [];

        if(isset($response['error'])){
            $result['error'] = $response['error'];
        }

        if(isset($response['counter']))
        {
            $result = [
                'counter' => ($counter + $response['counter']),
            ];
        }

        if(!$response)
        {
            $result['counter'] = $counter;
        }

        die(json_encode($result)); 
    }

    private function getAvailableCountries()
    {
        $type = Tools::getValue('type');

        if (!in_array($type, ['terminal', 'courier'])) {
            die(json_encode([]));
        }

        $context = Context::getContext();
        $context->smarty->assign([
            'hrx_available_countries' => $type === 'terminal' ? HrxDeliveryTerminal::getAvailableCountries() : HrxDeliveryCourier::getAvailableCountries(),
            ]
        );

        die(json_encode([
            'html' => $context->smarty->fetch(HrxDelivery::$_moduleDir . 'views/templates/admin/available_countries.tpl')
        ]));
    }
}