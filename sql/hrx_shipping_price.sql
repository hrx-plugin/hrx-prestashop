CREATE TABLE IF NOT EXISTS `_DB_PREFIX_hrx_shipping_price` (
    `id_price` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `country` varchar(2) NOT NULL,
    `price0_3` float(10) NOT NULL,
    `price3_5` float(10) NOT NULL,
    `price5_10` float(10) NOT NULL,
    `price10_15` float(10) NOT NULL,
    `price15_20` float(10) NOT NULL,
    `price20_30` float(10) NOT NULL,
    PRIMARY KEY (`id_price`)
) ENGINE=_MYSQL_ENGINE_ DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `_DB_PREFIX_hrx_shipping_price` 
    (`country`, `price0_3`, `price3_5`, `price5_10`, `price10_15`, `price15_20`, `price20_30`) 
VALUES 
    ('EE', 1, 2, 3, 4, 5, 6),
    ('FI', 1, 2, 3, 4, 5, 6),
    ('LT', 1, 2, 3, 4, 5, 6),
    ('LV', 1, 2, 3, 4, 5, 6),
    ('PL', 1, 2, 3, 4, 5, 6),
    ('SE', 1, 2, 3, 4, 5, 6);