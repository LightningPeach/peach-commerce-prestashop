<?php

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/sdk/LightningClient.php';
include dirname(__FILE__) . '/classes/PeachCommerceSql.php';
include dirname(__FILE__) . '/peachcommerce.php';

$defToken = Tools::substr(Tools::hash('peachCommerce/cron'), 0, 10);

$moduleManagerBuilder = ModuleManagerBuilder::getInstance();
$moduleManager = $moduleManagerBuilder->build();

if ($defToken !== Tools::getValue('token') || !$moduleManager->isInstalled(PeachCommerce::NAME)) {
    die('Bad token');
}


$config = Configuration::getMultiple(array(PeachCommerce::HOST, PeachCommerce::MERCHANT_ID));
if (!empty($config[PeachCommerce::HOST]) && !empty($config[PeachCommerce::MERCHANT_ID])) {
    $api = new \LightningHub\Hub\LightningClient($config[PeachCommerce::HOST], $config[PeachCommerce::MERCHANT_ID]);
} else {
    $api = null;
}

$waitStatus = (int)Configuration::get(PeachCommerce::OS_WAITING);
$canceledStatus = (int)Configuration::get('PS_OS_CANCELED');
$payedStatus = (int)Configuration::get('PS_OS_PAYMENT');

$sql = new DbQuery();
$sql->select('o.id_order, o.current_state, l.r_hash, l.creation_time, l.expiry');
$sql->leftJoin('peach_commerce', 'l', 'o.id_order = l.order_id');
$sql->from('orders', 'o');
$sql->where('o.module = "' . pSQL(PeachCommerce::NAME) . '"');
$sql->where('o.current_state = ' . pSQL($waitStatus));
$orders = Db::getInstance()->executeS($sql);

if (empty($orders)) {
    die(1);
}

try {
    foreach ($orders as $order) {
        $prestaOrder = new Order($order['id_order']);
        $expiryAt = (int)$order['creation_time'] + (int)$order['expiry'];
        $now = new \DateTime();
        if ($expiryAt <= $now->getTimestamp()) {
            $prestaOrder->setCurrentState($canceledStatus);
            $prestaOrder->save();
        } elseif ($order['current_state'] === $waitStatus && !empty($api)) {
            $invoice = $api->fetch($order['r_hash']);
            if ($invoice && $invoice->settled) {
                $prestaOrder->setCurrentState($payedStatus);
                $prestaOrder->save();

                $peachOrder = PeachCommerceSql::loadByOrderId($prestaOrder->id_order);
                $peachOrder->settled = true;
                $peachOrder->save();
            }
        }
    }
} catch (Exception $ex) {
    die($ex);
}
