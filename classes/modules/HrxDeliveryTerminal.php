<?php

class HrxDeliveryTerminal extends ObjectModel
{
    public $id_terminal;

    public $country;

    public $city;

    public $zip;

    public $address;

    public $latitude;

    public $longitude;

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
        'table' => 'hrx_delivery_terminal',
        'primary' => 'id',
        'fields' => [
            'id_terminal'      => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 255],
            'country'           => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 2, 'validate' => 'isLangIsoCode'],
            'city'              => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 128, 'validate' => 'isCityName'],
            'zip'               => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 10, 'validate' => 'isZipCodeFormat'],
            'address'           => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 255, 'validate' => 'isAddress'],
            'latitude'              => ['type' => self::TYPE_FLOAT, 'required' => true],
            'longitude'              => ['type' => self::TYPE_FLOAT, 'required' => true],
            'params'  => ['type' => self::TYPE_STRING,   'required' => true],
            'active'  => ['type' => self::TYPE_BOOL,   'required' => true, 'validate' => 'isBool'],
            'date_add'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        try {
            if ($id && !is_int($id)) {
                $id = Db::getInstance()->getValue('SELECT id FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' WHERE id_terminal = "' . pSQL($id) . '"');
            }
        } catch (\Throwable $th) {
            $id = null;
        }

        parent::__construct($id, $id_lang, $id_shop);
    }

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

    public static function isCountryAvailable($country)
    {
        return (bool) Db::getInstance()->getValue('
            SELECT 1 FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' 
            WHERE country = "' . pSQL(strtoupper($country)) . '"
                AND active = 1
        ');
    }

    public static function getAvailableCountries()
    {
        return Db::getInstance()->executeS('
            SELECT c.iso_code, cl.name FROM ' . _DB_PREFIX_ . 'country c
            LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON cl.id_country = c.id_country AND cl.id_lang = ' . (int) Context::getContext()->language->id . '
            WHERE EXISTS (
                SELECT * FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' hdt WHERE hdt.country = c.iso_code
            )'   
        );
    }

    public static function massAdd($data_array)
    {
        $sql_values = array_map(function ($item) {
            return self::getStringAsMySqlValues($item);
        }, $data_array);

        /*
        `max_length_cm`, `max_width_cm`, `max_height_cm`, `max_weight_kg`,
        `min_length_cm`, `min_width_cm`, `min_height_cm`, `min_weight_kg`,
        `recipient_phone_prefix`, `recipient_phone_regexp`,
        */
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . self::$definition['table'] . ' 
            (
                `id_terminal`, `country`, `city`, `zip`, `address`,
                `latitude`, `longitude`, `params`, `active`, `date_add`, `date_upd`
            )
            VALUES ' . implode(', ', $sql_values) . '
            ON DUPLICATE KEY UPDATE 
                `id_terminal` = VALUES(`id_terminal`), `country` = VALUES(`country`), `city` = VALUES(`city`),`zip` = VALUES(`zip`), 
                `address` = VALUES(`address`), `latitude` = VALUES(`latitude`), `longitude` = VALUES(`longitude`),
                `params` = VALUES(`params`), `active` = VALUES(`active`), `date_upd` = VALUES(`date_upd`)
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

            $row['id'] = $row['id_terminal'];

            return array_merge($row, $params);
        }, $result);
    }

    public static function getStringAsMySqlValues($data)
    {
        $data_keys = array_keys($data);
        $params = []; // holds fields that are not part of object
        foreach ($data_keys as $key) {
            // id from api is named id_terminal within object
            if ($key === 'id') {
                $key .= '_terminal';
            }

            if (isset(self::$definition['fields'][$key])) {
                continue;
            }

            $params[$key] = $data[$key];
        }

        $date = date('Y-m-d H:i:s');

        return "(
            '" . pSQL($data['id']) . "', '" . pSQL(strtoupper($data['country'])) . "', '" . pSQL($data['city']) . "',
            '" . pSQL($data['zip']) . "', '" . pSQL($data['address']) . "',
            '" . $data['latitude'] . "', '" . $data['longitude'] . "',
            '" . pSQL(json_encode($params)) . "', 1, '" . $date . "', '" . $date . "'
        )";
    }

    public static function addSqlTable()
    {
        return Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . self::$definition['table'] . "` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_terminal` varchar(255) NOT NULL,
                `country` varchar(2) NOT NULL,
                `city` varchar(128) NOT NULL,
                `zip` varchar(10) NOT NULL,
                `address` varchar(255) NOT NULL,
                `latitude`	DOUBLE NOT NULL,
                `longitude`	DOUBLE NOT NULL,
                `params` MEDIUMTEXT,
                `active` tinyint(1) NOT NULL DEFAULT '0',
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `id_terminal` (`id_terminal`),
                KEY `country` (`country`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public static function dropSqlTable()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS " . _DB_PREFIX_ . self::$definition['table']);
    }
}
