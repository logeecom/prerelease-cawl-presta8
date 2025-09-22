<?php

namespace CAWL\OnlinePayments\Classes\Utility;

use Db;
/**
 * Class DatabaseHandler
 *
 * @package OnlinePayments\Classes\Utility
 */
class DatabaseHandler
{
    /**
     * Creates table in database.
     *
     * @param string $name
     * @param int $indexNum
     *
     * @return bool
     */
    public static function createTable(string $name, int $indexNum, array $uniqueKeys = []) : bool
    {
        $indexColumns = '';
        for ($i = 1; $i <= $indexNum; $i++) {
            $indexColumns .= 'index_' . $i . '      VARCHAR(255),';
        }
        $keyDefs = '';
        if (!empty($uniqueKeys)) {
            $keys = \join(',', \array_map(function ($key) {
                return bqSQL($key);
            }, $uniqueKeys));
            $keyDefs = ", UNIQUE ({$keys})";
        }
        $sql = 'CREATE TABLE IF NOT EXISTS ' . bqSQL(_DB_PREFIX_ . "{$name}") . '(
  	        `id`           INT(64) unsigned NOT NULL AUTO_INCREMENT,
  	        `entity_type`         VARCHAR(255),' . $indexColumns . '
	        `data`         MEDIUMTEXT,
	         PRIMARY KEY (`id`)' . $keyDefs . '
             ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        return Db::getInstance()->execute($sql);
    }
    /**
     * Deletes table from database.
     *
     * @param string $name Name of database
     *
     * @return bool Result of drop table query
     */
    public static function dropTable(string $name) : bool
    {
        $script = 'DROP TABLE IF EXISTS ' . bqSQL(_DB_PREFIX_ . "{$name}");
        return Db::getInstance()->execute($script);
    }
}
