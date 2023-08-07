<?php

use Mijora\Hrx\DVDoug\BoxPacker\ItemList;
use Mijora\Hrx\DVDoug\BoxPacker\ParcelBox;
use Mijora\Hrx\DVDoug\BoxPacker\ParcelItem;
use Mijora\Hrx\DVDoug\BoxPacker\VolumePacker;

class HrxDeliveryFrontModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $result = "";
        if(Tools::getValue('init') == 'hrx_terminal')
        {
            $action = Tools::getValue('action');
            
            switch ($action) {
                case 'add':
                    $result = $this->addCartTerminal();
                    break;
                case 'getTerminals':
                    $result = $this->getTerminals();
                    break;
            }
        }  
        die(json_encode(['result' =>  $result]));
    }

    public function addCartTerminal()
    {
        $delivery_location_id = Tools::getValue('terminal_id');
        $id_cart = $this->context->cart->id;

        $id = HrxCartTerminal::getIdByCart($id_cart);
        $obj = new HrxCartTerminal($id);

        if(!Validate::isLoadedObject($obj))
        {
            $obj = new HrxCartTerminal();
            $obj->id_cart = $id_cart;
            $obj->delivery_location_id = $delivery_location_id;
            if($obj->add()){
                $result = 'Added cart terminal';
            }
        }
        else{
            $obj->id_cart = $id_cart;
            $obj->delivery_location_id = $delivery_location_id;
            if($obj->save()){
                $result = 'Updated cart terminal';
            }
        }
        return $result;
    }

    public function getTerminals()
    {
        $cart = $this->context->cart;

        $address = new Address($cart->id_address_delivery);
        $country_code = Country::getIsoById($address->id_country);

        if (empty($country_code)) {
            return [];
        }

        $item_list = HrxData::getItemListFromProductList($cart->getProducts(false, false));

        $terminal_list = HrxData::getTerminalsByCountry($country_code);

        $can_fit = [];

        $terminal_list = array_filter($terminal_list, function($terminal) use ($item_list, &$can_fit) {
            $location_max_weight = HrxData::getMaxWeight($terminal);
            $box_key = HrxData::getMaxDimensions($terminal, true) . ' ' . $location_max_weight; // to cache result for this kind of dimension box

            if (!isset($can_fit[$box_key])) {
                $can_fit[$box_key] = HrxData::doesParcelFitBox($terminal, $item_list);
            }

            return $can_fit[$box_key];
        });

        return $terminal_list;
    }
}
