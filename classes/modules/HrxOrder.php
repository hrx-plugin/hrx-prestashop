<?php

class HrxOrder extends ObjectModel
{
    public $id;     //order id

    public $id_shop;

    public $id_hrx;

    public $pickup_location_id;

    public $terminal;

    public $delivery_location_id;

    public $length;

    public $width;

    public $height;

    public $weight;

    public $tracking_number;

    public $tracking_url;

    public $status;

    public $status_code;

    public $kind;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hrx_order',
        'primary' => 'id',
        'fields' => [
            'id_shop'               => ['type' => self::TYPE_INT, 'required' => true, 'size' => 10],
            'id_hrx'                => ['type' => self::TYPE_STRING, 'size' => 36],
            'pickup_location_id'    => ['type' => self::TYPE_STRING, 'size' => 36],
            'terminal'              => ['type' => self::TYPE_STRING, 'size' => 255],
            'delivery_location_id'  => ['type' => self::TYPE_STRING, 'size' => 36],
            'length'                => ['type' => self::TYPE_FLOAT, 'size' => 10],
            'width'                 => ['type' => self::TYPE_FLOAT, 'size' => 10],
            'height'                => ['type' => self::TYPE_FLOAT, 'size' => 10],
            'weight'                => ['type' => self::TYPE_FLOAT, 'size' => 10],
            'tracking_number'       => ['type' => self::TYPE_STRING, 'size' => 32],
            'tracking_url'          => ['type' => self::TYPE_STRING, 'size' => 100],
            'status'                => ['type' => self::TYPE_INT, 'size' => 10],
            'status_code'           => ['type' => self::TYPE_STRING, 'size' => 15],
            'kind'                  => ['type' => self::TYPE_STRING, 'size' => 17],
            'date_add'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            ],
    ];

    public static function getOrdersWithoutTracking()
    {
        $query = (new DbQuery())
        ->from(self::$definition['table'])
        ->where('tracking_number = "" AND id_hrx != ""');

        $orders = Db::getInstance()->executeS($query);

        return $orders;
    }

}
