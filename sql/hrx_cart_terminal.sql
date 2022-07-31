CREATE TABLE IF NOT EXISTS `_DB_PREFIX_hrx_cart_terminal` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_cart` int(10) NOT NULL,
    `delivery_location_id` varchar(36) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `id_cart` (`id_cart`)
) ENGINE=_MYSQL_ENGINE_ DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;