<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($module)
{
    // Create parent tab "HRX Delivery"
    $parentTab = new Tab();
    $parentTab->active = 1;
    $parentTab->class_name = HrxDelivery::CONTROLLER_PARENT_TAB;
    $parentTab->name = array();
    $languages = Language::getLanguages(false);
    foreach ($languages as $language) {
        $parentTab->name[$language['id_lang']] = $module->l('HRX Delivery');
    }
    // Place in SELL section
    $ordersTabId = (int) Tab::getIdFromClassName('AdminParentOrders');
    $ordersTab = new Tab($ordersTabId);
    $parentTab->id_parent = (int) $ordersTab->id_parent;
    $parentTab->module = $module->name;
    if (!$parentTab->save()) {
        return false;
    }

    $parentTabId = (int) $parentTab->id;

    // Move existing child tabs under the new parent and rename them
    $tabsToUpdate = array(
        HrxDelivery::CONTROLLER_ORDER => $module->l('Orders'),
        HrxDelivery::CONTROLLER_WAREHOUSE => $module->l('Warehouses'),
        HrxDelivery::CONTROLLER_DELIVERY_COURIER => $module->l('Locations Courier'),
        HrxDelivery::CONTROLLER_DELIVERY_TERMINAL => $module->l('Locations Terminal'),
    );

    foreach ($tabsToUpdate as $controller => $newTitle) {
        $idTab = (int) Tab::getIdFromClassName($controller);
        if (!$idTab) {
            continue;
        }
        $tab = new Tab($idTab);
        if (!Validate::isLoadedObject($tab)) {
            continue;
        }

        $tab->id_parent = $parentTabId;
        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = $newTitle;
        }
        if (!$tab->save()) {
            return false;
        }
    }

    return true;
}
