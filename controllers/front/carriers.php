<?php

class MijoraVenipakCarriersModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if(Tools::isSubmit('id_carrier')) {
            $id_carrier = (int) Tools::getValue('id_carrier');
            $ps_carrier = new Carrier($id_carrier);
            $hrxDelivery = Module::getInstanceByName('hrxdelivery');
            $content = $hrxDelivery->hookDisplayCarrierExtraContent(
                [
                    'cart' => $this->context->cart,
                    'carrier' => (array) $ps_carrier
                ]
            );
            die(json_encode([
                'carrier_content' => $content,
                'hrx_map_template' => $this->context->smarty->fetch(MijoraVenipak::$_moduleDir . 'views/templates/front/map-template.tpl'),
            ]));
        }
    }
}