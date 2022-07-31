<?php

class HrxCartTerminal extends ObjectModel
{
    public $id;

    public $id_cart;

    public $delivery_location_id;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hrx_cart_terminal',
        'primary' => 'id',
        'fields' => [
            'id_cart'               => ['type' => self::TYPE_INT, 'required' => true, 'size' => 10],
            'delivery_location_id'  => ['type' => self::TYPE_STRING, 'size' => 36],
            'date_add'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            ],
        ];

    public static function getIdByCart($id_cart)
    {
        $query = (new DbQuery())
        ->select("id")
        ->from(self::$definition['table'])
        ->where('id_cart = ' . (int)$id_cart);

        return Db::getInstance()->getValue($query);
    }

    public static function getTerminalIdByCart($id_cart)
    {
        $query = (new DbQuery())
        ->select('delivery_location_id')
        ->from(self::$definition['table'])
        ->where('id_cart = ' . (int)$id_cart);

        return Db::getInstance()->getValue($query);
    }

}
