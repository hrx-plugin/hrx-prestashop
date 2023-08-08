<?php

class HrxDB
{
    const TABLES = [
        'hrx_cart_terminal' => "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "hrx_cart_terminal` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_cart` int(10) NOT NULL,
                `delivery_location_id` varchar(36) NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `id_cart` (`id_cart`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ",
        'hrx_order' => "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "hrx_order` (
                `id` int(10) unsigned NOT NULL,
                `id_shop` int(10) NOT NULL,
                `id_hrx` varchar(36),
                `pickup_location_id` varchar(36) NOT NULL,
                `terminal` varchar(255),
                `delivery_location_id` varchar(36) NOT NULL,
                `length` float(10) NOT NULL,
                `width` float(10) NOT NULL,
                `height` float(10) NOT NULL,
                `weight` float(10) NOT NULL,
                `tracking_number` varchar(32),
                `tracking_url` varchar(100),
                `status` int(10) NOT NULL,
                `status_code` varchar(15) NOT NULL,
                `kind` varchar(17) NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `id_shop` (`id_shop`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ",
        'hrx_warehouse' => "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "hrx_warehouse` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_warehouse` varchar(36) NOT NULL,
                `name` varchar(60) NOT NULL,
                `country` varchar(2),
                `city` varchar(40) NOT NULL,
                `address` varchar(50) NOT NULL,
                `zip` varchar(6) NOT NULL,
                `default_warehouse` tinyint NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ",
    ];

    /**
     * Create tables for module
     */
    public function createTables()
    {
        foreach (self::TABLES as $table => $query) {
            try {
                $res_query = Db::getInstance()->execute($query);

                if ($res_query === false) {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        // Added in v1.2.0
        try {
            HrxDeliveryCourier::addSqlTable();
            HrxDeliveryTerminal::addSqlTable();
        } catch (\Throwable $th) {
            return false;
        }

        return true;
    }

    /**
     * Delete module tables
     */
    public function deleteTables()
    {
        foreach (array_keys(self::TABLES) as $table) {
            try {
                Db::getInstance()->execute("DROP TABLE IF EXISTS " . _DB_PREFIX_ . $table);
            } catch (Exception $e) {
            }
        }

        // added in v1.2.0
        try {
            HrxDeliveryCourier::dropSqlTable();
            HrxDeliveryTerminal::dropSqlTable();
        } catch (\Throwable $th) {}

        return true;
    }

}
