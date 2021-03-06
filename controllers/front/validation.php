<?php

class PeachCommerceValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /** @var PeachCommerce $hub */
        $hub = $this->module;
        /** @var LightningHub\Hub\LightningClient $moduleApi */
        $hubApi = $hub->api;
        $cart = $this->context->cart;

        if (empty($cart->id_customer) ||
            empty($cart->id_address_delivery) ||
            empty($cart->id_address_invoice) ||
            !$hub->active
        ) {
            $this->module->logger->logError(
                'PeachCommerceValidationModuleFrontController->postProcess: id_customer or id_address_delivery or id_address_invoice empty or hub module not active',
                array(
                    'cart' => $cart,
                    'hubStatus' => $hub->active
                )
            );
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $hub::NAME) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            $this->module->logger->logError(
                'PeachCommerceValidationModuleFrontController->postProcess: Lightning payment method is not available.'
            );
            die($this->trans('Lightning payment method is not available.'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        try {
            $currency = $this->context->currency;
            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

            $paySatoshi = $hubApi->satoshiToBtc((float)$hubApi->getCurrency($currency->iso_code, $total));

            if ($paySatoshi > $hub->maxPayment()) {
                $orderAmountInBtc = $hub->satoshiToBtc($paySatoshi);
                $maxAmountInBtc = $hub->satoshiToBtc($hub->maxPayment());
                $maxAmountInFiat = round($maxAmountInBtc * $total / $orderAmountInBtc, 2);
                $this->errors[] = $this->trans('Order can\'t be completed.');
                $this->errors[] = $this->trans(
                    'Order amount ('
                    . $currency->
                    sign . $total
                    . ' ~ '
                    . $orderAmountInBtc
                    . ' BTC) exceeds max allowed amount by Lightning payment ('
                    . $maxAmountInBtc
                    . ' BTC ~ '
                    . $currency->sign
                    . $maxAmountInFiat
                    . ')'
                );
                $this->redirectWithNotifications($_SERVER['HTTP_REFERER']);
            }

            // CREATING ORDER
            $hub->validateOrder(
                (int)$cart->id,
                Configuration::get('PEACHCOMMERCE_OS_WAITING'),
                $total,
                $hub->displayName,
                null,
                array(),
                (int)$currency->id,
                false,
                $customer->secure_key
            );

            $orderId = (int)$hub->currentOrder;
            $orderReference = $hub->currentOrderReference;
            $invoice = $hubApi->invoice(array(
                'currency' => $currency->iso_code,
                'amount' => $total,
                'memo' => json_encode(array(
                    'order_id' => $orderId,
                    'name' => Configuration::get('PS_SHOP_NAME') . ', Reference: ' . $orderReference
                )),
            ));

            if (!$invoice) {
                $this->module->logger->logError(
                    'PeachCommerceValidationModuleFrontController->postProcess: Invoice not generated'
                );
                $order = new Order($orderId);
                $order->delete();

                $this->errors[] = $this->trans('Payment request can\'t be generated. Try later.');
                $this->redirectWithNotifications($_SERVER['HTTP_REFERER']);
            }

            $hub->addOrderInfo($orderId, $invoice);
            $queryData = array(
                'controller' => 'order-confirmation',
                'id_cart' => (int)$cart->id,
                'id_module' => (int)$hub->id,
                'id_order' => $hub->currentOrder,
                'key' => $customer->secure_key,
            );
            Tools::redirect('index.php?' . http_build_query($queryData));
        } catch (\Exception $error) {
            $this->module->logger->logError(
                'PeachCommerceValidationModuleFrontController->postProcess: Exception',
                array('message' => $error->getMessage())
            );
            $this->context->smarty->assign('error', $error->getMessage());
            $this->setTemplate('module:peachcommerce/views/templates/front/errors-messages.tpl');
        }
    }
}
