<?php

class HrxDB
{
    const TABLES = [
        'hrx_order',
        'hrx_warehouse',
        'hrx_cart_terminal',
        'hrx_shipping_price',
    ];
    /**
     * Create tables for module
     */
    public function createTables()
    {
        $sql_path = dirname(__FILE__) . '/../sql/';
        $sql_files = scandir($sql_path);
        $sql_queries = [];
        foreach($sql_files as $sql_file)
        {
            $file_parts = pathinfo($sql_file);
            if($file_parts['extension'] == 'sql')
            {
                $sql_query = str_replace('_DB_PREFIX_', _DB_PREFIX_, Tools::file_get_contents($sql_path . $sql_file));
                $sql_queries[] = str_replace('_MYSQL_ENGINE_', _MYSQL_ENGINE_, $sql_query);
            }
        }
        foreach ($sql_queries as $query) {
            try {
                $res_query = Db::getInstance()->execute($query);

                if ($res_query === false) {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        }

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
        foreach (self::TABLES as $table) {
            try {
                Db::getInstance()->execute("DROP TABLE IF EXISTS " . _DB_PREFIX_ . $table);
            } catch (Exception $e) {
            }
        }

        try {
            HrxDeliveryCourier::dropSqlTable();
            HrxDeliveryTerminal::dropSqlTable();
        } catch (\Throwable $th) {}

        return true;
    }

}
