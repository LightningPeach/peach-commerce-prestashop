<?php

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/peachcommerce.php';

$defToken = Tools::substr(Tools::hash('peachCommerce/cron'), 0, 10);

$moduleManagerBuilder = ModuleManagerBuilder::getInstance();
$moduleManager = $moduleManagerBuilder->build();

if ($defToken !== Tools::getValue('token') || !$moduleManager->isInstalled(PeachCommerce::NAME)) {
    die('Bad token');
}

$sql = new DbQuery();
$sql->select('o.id_order, l.creation_time, l.expiry');
$sql->leftJoin('peach_commerce', 'l', 'o.id_order = l.order_id');
$sql->from('orders', 'o');
$sql->where('o.module = "' . pSQL(PeachCommerce::NAME) . '"');
$sql->where('o.current_state = ' . pSQL(Configuration::get(PeachCommerce::OS_WAITING)));
$orders = Db::getInstance()->executeS($sql);
if (empty($orders)) {
    die(1);
}
try {
    foreach ($orders as $order) {
        $expiryAt = (int)$order['creation_time'] + (int)$order['expiry'];
        $now = new \DateTime();
        if ($expiryAt <= $now->getTimestamp()) {
            $orderObj = new Order($order['id_order']);
            $orderObj->setCurrentState((int)Configuration::get('PS_OS_CANCELED'));
            $orderObj->save();
        }
    }
} catch (Exception $ex) {
    die($ex);
}
