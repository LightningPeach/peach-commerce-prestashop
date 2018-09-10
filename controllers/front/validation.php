<?php

class LightningHubValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if (
            empty($cart->id_customer) ||
            empty($cart->id_address_delivery) ||
            empty($cart->id_address_invoice) ||
            !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'LightningHub') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('Lightning payment method is not available.', array(), 'Modules.Checkpayment.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('LIGHTNINGHUB_OS_WAITING'),
            $total,
            $this->module->displayName,
            null,
            array(),
            (int)$currency->id,
            false,
            $customer->secure_key
        );
        $orderId = (int)$this->module->currentOrder;

        $invoice = $this->module->api->invoice([
            'currency' => $currency->iso_code,
            'amount' => $total,
            'memo' => json_encode(['order_id' => $orderId]),
        ]);
        $this->module->addOrderInfo($orderId, $invoice);

        $queryData = array(
            'controller' => 'order-confirmation',
            'id_cart' => (int)$cart->id,
            'id_module' => (int)$this->module->id,
            'id_order' => $this->module->currentOrder,
            'key' => $customer->secure_key,
        );
        Tools::redirect('index.php?' . http_build_query($queryData));
    }

    protected function isValidOrder()
    {
        return true;
    }
}
