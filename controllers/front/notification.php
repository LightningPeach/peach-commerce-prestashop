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
        if (!isset($data->description)) {
            $this->module->logger->logError(
                'PeachCommerceNotificationModuleFrontController->postProcess: Payment description not provided.',
                array('data_from_hub' => $data)
            );
            echo json_encode(array('ok' => false));
            die();
        }
        $orderId = json_decode($data->description);
        if (!isset($orderId->order_id) || $orderId->order_id < 1) {
            $this->module->logger->logError(
                'PeachCommerceNotificationModuleFrontController->postProcess: No orderId in description',
                array('data_from_hub' => $data)
            );
            echo json_encode(array('ok' => false));
            die();
        }
        $orderObj = PeachCommerceSql::loadByOrderId($orderId->order_id);
        if (empty($orderObj)) {
            $this->module->logger->logDebug(
                'PeachCommerceNotificationModuleFrontController->postProcess: Not found peach order with provided data',
                array('data_from_hub' => $data)
            );
            echo json_encode(array('ok' => false));
            die();
        }
        $invoice = $this->module->api->fetch($orderObj->r_hash);
        if (empty($invoice)) {
            $this->module->logger->logDebug(
                'PeachCommerceNotificationModuleFrontController->postProcess: Cant load invoice',
                array('peachOrder' => $orderObj)
            );
            echo json_encode(array('ok' => false));
            die();
        }
        if ($invoice && $invoice->settled) {
            $this->module->logger->logDebug(
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
