<?php

class PeachCommerceRPreImageModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
    }

    public function postProcess()
    {
        $logger = new FileLogger(0);
        $logger->setFilename(_PS_ROOT_DIR_ . '/rpreimage.log');
        $logger->logDebug('--------------------');
        $logger->logDebug(Tools::file_get_contents('php://input'));
        $logger->logDebug(Tools::getAllValues());
        $amount = Tools::getValue('amount');
        $description = Tools::getValue('description');
        $creation_date = Tools::getValue('creation_date');
        $expiry = Tools::getValue('expiry');
        $settle_date = Tools::getValue('settle_date');
        $settled = Tools::getValue('settled');
        $payment_request = Tools::getValue('payment_request');
        $r_hash = Tools::getValue('r_hash');
        $withdraw_tx = Tools::getValue('withdraw_tx');
        $fee = Tools::getValue('fee');
        $amount_without_fee = Tools::getValue('amount_without_fee');

        $logger->logDebug($amount);
        $logger->logDebug($description);
        $logger->logDebug($creation_date);
        $logger->logDebug($expiry);
        $logger->logDebug($settle_date);
        $logger->logDebug($settled);
        $logger->logDebug($payment_request);
        $logger->logDebug($r_hash);
        $logger->logDebug($withdraw_tx);
        $logger->logDebug($fee);
        $logger->logDebug($amount_without_fee);

        header('Content-Type: application/json');
        echo json_encode(array('hello' => 'world'));
        die();
    }
}
