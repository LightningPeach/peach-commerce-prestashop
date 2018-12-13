<?php

class PeachCommerceOrderStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
    }

    public function displayAjax()
    {
        header('Content-Type: application/json');
        if (!$this->isTokenValid()) {
            die(1);
        }
        $orderId = (int)Tools::getValue('order_id');
        $order = new Order($orderId);
        $orderCustomerId = (int)$order->id_customer;
        $customerId = (int)$this->context->customer->id;
        if ($orderCustomerId !== $customerId) {
            die(1);
        }
        $waiting = $order->getCurrentOrderState()->id === (int)Configuration::get(PeachCommerce::OS_WAITING);
        $this->ajaxDie(json_encode(array('reload' => !$waiting)));
    }
}
