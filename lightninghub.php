<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . 'lightninghub/sdk/LightningClient.php');
include_once(_PS_MODULE_DIR_ . 'lightninghub/classes/LightningHubSql.php');

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use LightningHub\Hub;

class LightningHub extends PaymentModule
{
    const HOST = 'LIGHTNINGHUB_HOST';
    const MERCHANT_ID = 'LIGHTNINGHUB_MERCHANT_ID';
    const OS_WAITING = 'LIGHTNINGHUB_OS_WAITING';
    const WALLET_PREFIX = 'lightning:';

    private $postErrors = [];
    private $config_form = false;
    private $hubHost;
    private $merchantId;

    public $api;

    public function __construct()
    {
        $this->name = 'LightningHub';
        $this->tab = 'payments_gateways';
        $this->version = '0.0.1';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'LightningPeach';
        $this->need_instance = 0;
        $this->is_eu_compatible = 1;
        $this->bootstrap = true;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array(self::HOST, self::MERCHANT_ID));
        if (!empty($config[self::HOST])) {
            $this->hubHost = $config[self::HOST];
        }
        if (!empty($config[self::MERCHANT_ID])) {
            $this->merchantId = $config[self::MERCHANT_ID];
        }

        parent::__construct();

        $this->displayName = $this->l('Lightning Hub');
        $this->description = $this->l('Accept payments by lightning payment.');

        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (empty($this->hubHost) || empty($this->merchantId)) {
            $this->warning = $this->l(
                'Lightning hub host and merchant id must be configured before using this module'
            );
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
        $this->api = new Hub\LightningClient($this->hubHost, $this->merchantId);
    }

    public function getHubHost()
    {
        return $this->hubHost;
    }

    public function getMerchantId()
    {
        return $this->merchantId;
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');

            return false;
        }

        return parent::install() &&
            LightningHubSql::install() &&
            $this->installOrderStatus() &&
            $this->registerHook('header') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions');
    }

    public function installOrderStatus()
    {
        if (!Configuration::get(self::OS_WAITING)
            || !Validate::isLoadedObject(new OrderState(Configuration::get(self::OS_WAITING)))) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                $order_state->name[$language['id_lang']] = 'Awaiting for Lightning payment';
            }
            $order_state->send_email = false;
            $order_state->color = '#4169E1';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            if ($order_state->add()) {
                $source = _PS_MODULE_DIR_ . $this->name . '/views/img/option_logo.png';
                $destination = _PS_ROOT_DIR_ . '/img/os/' . (int)$order_state->id . '.gif';
                copy($source, $destination);
            }
            Configuration::updateValue(self::OS_WAITING, (int)$order_state->id);
        }

        return true;
    }

    public function uninstall()
    {
        return Configuration::deleteByName(self::HOST) &&
            Configuration::deleteByName(self::MERCHANT_ID) &&
            parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $html = '';
        if (((bool)Tools::isSubmit('submitLightningHubModule')) == true) {
            $this->postProcess();
            if (count($this->postErrors)) {
                foreach ($this->postErrors as $err) {
                    $html .= $this->displayError($err);
                }
                $html .= '<br/>';
            }
        }

        try {
            $balanceReq = $this->api->getBalance();
        } catch (Hub\LightningException $error) {
            $balanceReq = null;
        }
        if (!$balanceReq) {
            $balance = 0;
        } else {
            $balance = $balanceReq->balance;
        }

        $this->context->smarty->assign('balance', $this->formatBTC($this->convertToBTCFromSatoshi($balance)));
        $this->context->smarty->assign(
            'notificationUrl',
            $this->context->link->getModuleLink($this->name, 'notification', array(), true)
        );
        $this->context->smarty->assign(
            'rPreImage',
            $this->context->link->getModuleLink($this->name, 'rpreimage', array(), true)
        );
        $html .= $this->display(__FILE__, 'config_info.tpl');

        $this->context->smarty->assign('module_dir', $this->_path);
        $html .= $this->renderForm();

        return $html;
    }

    protected function getConfigFormValues()
    {
        return array(
            self::HOST => Configuration::get(self::HOST, null),
            self::MERCHANT_ID => Configuration::get(self::MERCHANT_ID, null),
        );
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLightningHubModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&' .
            http_build_query(
                array(
                    'configure' => $this->name,
                    'tab_module' => $this->tab,
                    'module_name' => $this->name,
                )
            );
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a hub host'),
                        'name' => self::HOST,
                        'label' => $this->l('Hub host'),
                        'required' => true,
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => self::MERCHANT_ID,
                        'label' => $this->l('Merchant id'),
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function postProcess()
    {
        $host = Tools::getValue(self::HOST);
        $merchantId = Tools::getValue(self::MERCHANT_ID);

        if (!$host) {
            $this->postErrors[] = $this->l('Hub host is required.');
        } elseif (!filter_var($host, FILTER_VALIDATE_URL)) {
            $this->postErrors[] = $this->l('Hub host is not url.');
        } else {
            Configuration::updateValue(self::HOST, $host);
        }
        if (!$merchantId) {
            $this->postErrors[] = $this->l('Merchant id is required.');
        } else {
            Configuration::updateValue(self::MERCHANT_ID, $merchantId);
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the shop frontend (user view).
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addJS($this->_path . '/views/js/libs/jquery-qrcode/jquery.qrcode.min.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return null;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }

        $newOption = new PaymentOption();
        $newOption
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/option_logo.png'))
            ->setCallToActionText($this->l('Pay by Lightning'))
            ->setModuleName($this->name)
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation(
                $this->fetch('module:LightningHub/views/templates/front/payment_info.tpl')
            );
        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return null;
        }

        $order = $params['order'];
        $currency = new Currency($params['order']->id_currency);
        $totalPaid = $order->getOrdersTotalPaid();

        try {
            $BTC = $this->api->getCurrency(strtolower($currency->iso_code), $totalPaid);
            $BTC .= '  BTC';
        } catch (Hub\LightningException $e) {
            $BTC = $e->getMessage();
        }

        $orderObj = LightningHubSql::loadByOrderId($order->id);
        $payReq = $orderObj->payment_request;
        $expiryAt = $orderObj->creation_time + $orderObj->expiry;

        $walletBtn = self::WALLET_PREFIX . $payReq;

        $canceled = $order->getCurrentOrderState()->id === (int)Configuration::get('PS_OS_CANCELED');
        $now = new \DateTime();
        if ($expiryAt <= $now->getTimestamp()) {
            $order->setCurrentState((int)Configuration::get('PS_OS_CANCELED'));
            $order->save();
            $canceled = true;
        }
        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(
            array(
                'id_order' => $order->id,
                'reference' => $order->reference,
                'shop_name' => $this->context->shop->name,
                'params' => $params,
                'total' => Tools::displayPrice($totalPaid, $currency, false),
                'BTC' => $BTC,
                'payReq' => $payReq,
                'walletBtn' => $walletBtn,
                'expiry_at' => $expiryAt,
                'settled' => $orderObj->settled,
                'canceled' => $canceled,
                'order_status' => $order->getCurrentOrderState()->name[1],
            )
        );

        return $this->fetch('module:LightningHub/views/templates/hook/order_complete.tpl');
    }

    public function addOrderInfo($orderId, $invoice)
    {
        $order = new LightningHubSql();
        $order->order_id = $orderId;
        $order->payment_request = $invoice->payment_request;
        $order->r_hash = $invoice->r_hash;
        $order->settled = false;
        $order->creation_time = $invoice->creation_time;
        $order->expiry = $invoice->expiry;
        $order->timestamp = $invoice->timestamp;
        $order->signature = $invoice->signature;
        $order->save();
    }

    public function convertToBTCFromSatoshi($value)
    {
        $BTC = $value / 100000000;
        return $BTC;
    }

    public function formatBTC($value)
    {
        $value = sprintf('%.8f', $value);
        $value = rtrim($value, '0') . ' BTC';
        return $value;
    }
}
