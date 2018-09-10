<?php

class LightningHubSql extends ObjectModel
{
    public $order_id;
    public $payment_request;
    public $r_hash;
    public $settled;
    public $creation_time;
    public $expiry;
    public $timestamp;
    public $signature;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'lightning_hub',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'order_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'payment_request' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'r_hash' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'settled' => array('type' => self::TYPE_BOOL),
            'creation_time' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'expiry' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'timestamp' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'signature' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
        )
    );

    /**
     * Install module tables
     * @return bool
     */
    public static function install()
    {
        $sql = array();

        $sql[] = '
          CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lightning_hub` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL REFERENCES ' . _DB_PREFIX_ . 'orders(id),
            `payment_request` VARCHAR(512) NOT NULL,
            `r_hash` VARCHAR(255) NOT NULL,
            `settled` TINYINT(1) NOT NULL DEFAULT 0,
            `creation_time` INT(11) NOT NULL,
            `expiry` INT(11) NOT NULL,
            `timestamp` INT(11) NOT NULL,
            `signature` VARCHAR(255) NOT NULL,
            PRIMARY KEY  (`id`)
          ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;
          ';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    public static function uninstall()
    {
        $sql = array();

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }
        return true;
    }

    public static function loadByOrderId($orderId)
    {
        $sql = new DbQuery();
        $sql->select('id');
        $sql->from('lightning_hub');
        $sql->where('order_id = ' . pSQL((int)$orderId));
        $order = Db::getInstance()->getValue($sql);
        return new self($order);
    }
}