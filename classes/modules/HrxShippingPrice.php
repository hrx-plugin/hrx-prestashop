<?php

class HrxShippingPrice extends ObjectModel
{
    public $id_price;

    public $country;

    public $price0_3;

    public $price3_5;

    public $price5_10;

    public $price10_15;

    public $price15_20;

    public $price20_30;

    private static $weights = [
        'price0_3'  => ['from' => 0, 'to' => 2.99],
        'price3_5'  => ['from' => 3, 'to' => 4.99],
        'price5_10' => ['from' => 5, 'to' => 9.99],
        'price10_15' => ['from' => 10, 'to' => 14.99],
        'price15_20' => ['from' => 15, 'to' => 19.99],
        'price20_30' => ['from' => 20, 'to' => 30]
    ];

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hrx_shipping_price',
        'primary' => 'id_price',
        'fields' => [
            'country'       => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 2],
            'weight_from'   => ['type' => self::TYPE_FLOAT, 'required' => true, 'size' => 10],
            'country'       => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 10],
            'weight_to'     => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 10],
            'price'         => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 10],
        ],
    ];

    public static function getAllTable()
    {
        $query = (new DbQuery())
        ->from(self::$definition['table']);

        return Db::getInstance()->executeS($query, true);
    }

    public static function getPrice($w, $country)
    {
        $query = (new DbQuery())
        ->from(self::$definition['table'])
        ->where('country = "' . $country . '"');

        $row = Db::getInstance()->executeS($query, true);

        if($row)
        {
            $price_key = null;
            foreach(self::$weights as $key => $weight){
                if($w >= $weight['from'] && $w <= $weight['to']){
                    $price_key = $key;
                }
            }
    
            if($price_key){
                return $row[0][$price_key];
            }
        }

        return false;
    }

    public static function updateTable($data)
    {
        $sql = "TRUNCATE TABLE " . _DB_PREFIX_ . self::$definition['table'] . "; ";
        $sql .= "INSERT INTO " . _DB_PREFIX_ . self::$definition['table'] . "
        (`country`, `price0_3`, `price3_5`, `price5_10`, `price10_15`, `price15_20`, `price20_30`) 
            VALUES ";
        
        foreach($data as $country => $item)
        {
            $sql .= "('" . $country. "'," . (int)$item['price0_3'] . "," . (int)$item['price3_5'] . "," . (int)$item['price5_10'] . "," . (int)$item['price10_15'] . "," . (int)$item['price15_20'] . "," . (int)$item['price20_30'] . "),";
        }

        $sql = substr_replace($sql ,";", -1);

        $result = DB::getInstance()->execute($sql);

        return $result;
                
    }
}
