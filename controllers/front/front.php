<?php
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
                case 'delete':
                    $result = $this->deleteCartTerminal();
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

    public function deleteCartTerminal()
    {
        $delivery_location_id = Tools::getValue('terminal_id');
        $id_cart = $this->context->cart->id;

        $id = HrxCartTerminal::getIdByCart($id_cart);
        $obj = new HrxCartTerminal($id);
        if(Validate::isLoadedObject($obj))
        {
            $result = $obj->delete();
        }
        return $result;
    }

    public function getTerminals()
    {
        $address = new Address($this->context->cart->id_address_delivery);
        $country_code = Country::getIsoById($address->id_country);

        if (empty($country_code)) {
            return [];
        }

        return HrxData::getTerminalsByCountry($country_code);

    }

}
