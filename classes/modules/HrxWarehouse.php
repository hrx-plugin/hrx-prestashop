<?php

class HrxWarehouse extends ObjectModel
{
    public $id_warehouse;

    public $name;

    public $country;

    public $city;

    public $zip;

    public $address;

    public $defaul_warehouse;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hrx_warehouse',
        'primary' => 'id',
        'fields' => [
            'id_warehouse'      => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 36],
            'name'              => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 60, 'validate' => 'isGenericName'],
            'country'           => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 2, 'validate' => 'isLangIsoCode'],
            'city'              => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 40, 'validate' => 'isCityName'],
            'zip'               => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 6, 'validate' => 'isZipCodeFormat'],
            'address'           => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 50, 'validate' => 'isAddress'],
            'default_warehouse'  => ['type' => self::TYPE_BOOL,   'required' => true, 'validate' => 'isBool'],
            'date_add'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            ],
        ];

    public static function changeDefaultWarehouse($id)
    {
        //reset default warehouse
        $query = 'UPDATE '. _DB_PREFIX_ . self::$definition['table'] . ' SET `default_warehouse` = 0';
        DB::getInstance()->execute($query);

        //make default warehouse
        $query = 'UPDATE '. _DB_PREFIX_ . self::$definition['table'] . ' SET `default_warehouse` = 1 WHERE `id` = ' . $id;
        DB::getInstance()->execute($query);
    }

    public static function getDefaultWarehouseId()
    {
        $query = (new DbQuery())
        ->select("id_warehouse")
        ->from(self::$definition['table'])
        ->where('default_warehouse = 1');

        return Db::getInstance()->getValue($query);
    }

    public static function getWarehouses()
    {
        $query = (new DbQuery())
        ->from(self::$definition['table']);

        return Db::getInstance()->executeS($query);
    }

    public static function getName($id_warehouse)
    {
        $query = (new DbQuery())
        ->select("name")
        ->from(self::$definition['table'])
        ->where('id_warehouse = "' . $id_warehouse . '"');

        return Db::getInstance()->getValue($query);
    }

}
