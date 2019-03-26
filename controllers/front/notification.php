<?php

include_once(_PS_MODULE_DIR_ . 'peachcommerce/classes/PeachCommerceSql.php');

class PeachCommerceNotificationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
    }

    public function postProcess()
    {
        $data = json_decode(Tools::file_get_contents('php://input'));
        if (!isset($data->amount) ||
            !isset($data->description) ||
            !isset($data->creation_date) ||
            !isset($data->expiry) ||
            !isset($data->settle_date) ||
            !isset($data->settled) ||
            !isset($data->payment_request) ||
            !isset($data->r_hash) ||
            !isset($data->withdraw_tx) ||
            !isset($data->fee) ||
            !isset($data->amount_without_fee)
        ) {
            $this->module->logError(
                'PeachCommerceNotificationModuleFrontController->postProcess: Some data not provided. Required [amount, description, creation_date, expiry, settle_date, settled, payment_request, r_hash, withdraw_tx, fee, amount_without_fee]',
                array('data_from_hub' => $data)
            );
            echo json_encode(array('ok' => false));
            die();
        }
        $orderId = json_decode($data->description);
        if (!isset($orderId->order_id) || $orderId->order_id < 1) {
            $this->module->logError(
                'PeachCommerceNotificationModuleFrontController->postProcess: No orderId in description',
                array('data_from_hub' => $data)
            );
            echo json_encode(array('ok' => false));
            die();
        }
        $orderObj = PeachCommerceSql::loadByOrderId($orderId->order_id);
        $invoice = $this->module->api->fetch($orderObj->r_hash);
        if ($invoice->settled) {
            $this->module->logDebug(
                'PeachCommerceNotificationModuleFrontController->postProcess: Invoice settled',
                array('invoice_from_hub' => $invoice)
            );
            $order = new Order((int)$orderObj->order_id);
            $order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));
            $order->save();

            $orderObj->settled = true;
            $orderObj->save();
        }

        header('Content-Type: application/json');
        echo json_encode(array('ok' => true));
        die();
    }
}
