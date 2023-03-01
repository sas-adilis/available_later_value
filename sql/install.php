<?php

$sql = [
    'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'available_later_value` (
        `id_available_later_value` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `reference` varchar(64) DEFAULT \'\',
        `delay_in_days` int(5) DEFAULT \'0\',
        PRIMARY KEY (`id_available_later_value`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;',

    'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'available_later_value_lang` (
        `id_available_later_value` int(11) unsigned NOT NULL,
        `id_lang` int(10) unsigned NOT NULL,
        `name` varchar(128) NOT NULL,
        `description_short` varchar(255) DEFAULT \'\',
        `description` TEXT DEFAULT \'\',
         PRIMARY KEY (`id_available_later_value`,`id_lang`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
];

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

$extra_fields = [
    'product' => [
        [
            'field' => 'id_available_later_value',
            'query' => "ADD id_available_later_value INT(11) unsigned DEFAULT NULL"
        ],
    ],
    'orders' => [
        [
            'field' => 'date_shipping_estimated',
            'query' => "ADD date_shipping_estimated date DEFAULT NULL"
        ],
    ],
    'order_detail' => [
        [
            'field' => 'available_later_value',
            'query' => "ADD available_later_value varchar(25) DEFAULT NULL"
        ],
    ]
];

foreach($extra_fields as $table => $fields) {
    $definitions = Db::getInstance()->executeS('DESCRIBE '._DB_PREFIX_.$table);
    foreach($fields as $field) {
        $field_exists = false;
        foreach($definitions as $definition) {
            if ($field['field'] == $definition['Field']) {
                $field_exists = true;
                break;
            }
        }
        if (!$field_exists) {
            if (!Db::getInstance()->execute('ALTER TABLE '._DB_PREFIX_.$table.' '.$field['query'])) {
                return false;
            }
        }
    }
}