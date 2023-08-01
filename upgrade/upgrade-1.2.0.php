<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0($module)
{
    $db = Db::getInstance();

    $query = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "hrx_shipping_price`";
    $db->execute($query);

    // add delivery locations sql tables
    if (!HrxDeliveryCourier::addSqlTable() || !HrxDeliveryTerminal::addSqlTable()) {
        return false;
    }

    // add new tabs
    $tabs = [
        HrxDelivery::CONTROLLER_DELIVERY_COURIER => array(
            'title' => $module->l('HRX Locations Courier'),
            'parent_tab' => (int) Tab::getIdFromClassName('AdminParentShipping')
        ),
        HrxDelivery::CONTROLLER_DELIVERY_TERMINAL => array(
            'title' => $module->l('HRX Locations Terminal'),
            'parent_tab' => (int) Tab::getIdFromClassName('AdminParentShipping')
        ),
    ];

    foreach ($tabs as $controller => $tabData) {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $controller;
        $tab->name = array();
        $languages = Language::getLanguages(false);

        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = $tabData['title'];
        }

        $tab->id_parent = $tabData['parent_tab'];
        $tab->module = $module->name;
        if (!$tab->save()) {
            return false;
        }
    }

    // cleanup module files that are no longer used
    if (is_dir(HrxDelivery::$_moduleDir . 'data')) {
        $data_files = glob(HrxDelivery::$_moduleDir . 'data/*.json');
        if ($data_files) {
            foreach ($data_files as $path) {
                try {
                    unlink($path);
                } catch (\Throwable $th) {}
            }
        }
    }

    if (is_dir(HrxDelivery::$_moduleDir . 'pdf/returnLabels')) {
        $return_labels = glob(HrxDelivery::$_moduleDir . 'pdf/returnLabels/*.pdf');
        if ($return_labels) {
            foreach ($return_labels as $path) {
                try {
                    unlink($path);
                } catch (\Throwable $th) {}
            }
        }
    }

    if (is_dir(HrxDelivery::$_moduleDir . 'pdf/shippingLabels')) {
        $shipping_labels = glob(HrxDelivery::$_moduleDir . 'pdf/shippingLabels/*.pdf');
        if ($shipping_labels) {
            foreach ($shipping_labels as $path) {
                try {
                    unlink($path);
                } catch (\Throwable $th) {}
            }
        }
    }

    return true;
}
