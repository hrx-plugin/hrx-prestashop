<?php

class HrxDeliveryCourier extends ObjectModel
{
    public $country;

    public $params;

    public $active;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    private $params_array;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hrx_delivery_courier',
        'primary' => 'id',
        'fields' => [
            'country'           => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 2, 'validate' => 'isLangIsoCode'],
            'params'  => ['type' => self::TYPE_STRING,   'required' => true],
            'active'  => ['type' => self::TYPE_BOOL,   'required' => true, 'validate' => 'isBool'],
            'date_add'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function getParams()
    {
        if(!$this->params) {
            return [];
        }

        if (!$this->params_array) {
            $this->params_array = json_decode((string) $this->params, true);
        }

        return $this->params_array;
    }

    public static function getAvailableCountries()
    {
        return Db::getInstance()->executeS('
            SELECT c.iso_code, cl.name FROM ' . _DB_PREFIX_ . 'country c
            LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON cl.id_country = c.id_country AND cl.id_lang = ' . (int) Context::getContext()->language->id . '
            WHERE EXISTS (
                SELECT * FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' hdc WHERE hdc.country = c.iso_code
            )'   
        );
    }

    public static function getDeliveryCourierByCountry($country)
    {
        $id = Db::getInstance()->getValue('SELECT id FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' WHERE country = "' . pSQL(strtoupper($country)) . '"');

        if (!$id) {
            return null;
        }

        return new HrxDeliveryCourier($id);
    }

    public static function massAdd($data_array)
    {
        $sql_values = array_map(function ($item) {
            return self::getStringAsMySqlValues($item);
        }, $data_array);

        $sql = 'INSERT INTO ' . _DB_PREFIX_ . self::$definition['table'] . ' 
            (
                `country`, `params`, `active`, `date_add`, `date_upd`
            )
            VALUES ' . implode(', ', $sql_values) . '
            ON DUPLICATE KEY UPDATE 
                `country` = VALUES(`country`), `params` = VALUES(`params`), 
                `active` = VALUES(`active`), `date_upd` = VALUES(`date_upd`)
        ';

        try {
            Db::getInstance()->execute($sql);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function disableAll()
    {
        Db::getInstance()->execute('
            UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . ' SET active = 0
        ');
    }

    public static function getLocationListByCountry($country)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` WHERE country = "' . pSQL(strtoupper($country)) . '" AND active = 1';

        $result = Db::getInstance()->executeS($sql);

        if (!$result) {
            return [];
        }

        return array_map(function ($row) {
            $params = [];
            if ($row['params']) {
                $params = json_decode((string) $row['params'], true);
            }

            unset($row['params']);

            if (!is_array($params)) {
                $params = [];
            }

            return array_merge($row, $params);
        }, $result);
    }

    public static function getStringAsMySqlValues($data)
    {
        $data_keys = array_keys($data);
        $params = []; // holds fields that are not part of object
        foreach ($data_keys as $key) {
            if (isset(self::$definition['fields'][$key])) {
                continue;
            }

            $params[$key] = $data[$key];
        }

        $date = date('Y-m-d H:i:s');

        return "(
            '" . pSQL(strtoupper($data['country'])) . "', '" . pSQL(json_encode($params)) . "', 1, '" . $date . "', '" . $date . "'
        )";
    }

    public static function addSqlTable()
    {
        return Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . self::$definition['table'] . "` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `country` varchar(2) NOT NULL,
                `params` MEDIUMTEXT,
                `active` tinyint(1) NOT NULL DEFAULT '0',
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `country` (`country`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public static function dropSqlTable()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS " . _DB_PREFIX_ . self::$definition['table']);
    }
}
