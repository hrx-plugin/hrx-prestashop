CREATE TABLE IF NOT EXISTS `_DB_PREFIX_hrx_warehouse` (
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
) ENGINE=_MYSQL_ENGINE_ DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;