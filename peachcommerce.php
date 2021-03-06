<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . 'peachcommerce/sdk/LightningClient.php');
include_once(_PS_MODULE_DIR_ . 'peachcommerce/classes/PeachCommerceSql.php');

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use LightningHub\Hub;

class PeachCommerce extends PaymentModule
{
    const NAME = 'peachcommerce';
    const HOST = 'PEACHCOMMERCE_HOST';
    const MERCHANT_ID = 'PEACHCOMMERCE_MERCHANT_ID';
    const OS_WAITING = 'PEACHCOMMERCE_OS_WAITING';
    const LOGGER_TYPE = 'PEACHCOMMERCE_LOGGER_TYPE';
    const LOGGER_FILE = 'PEACHCOMMERCE_LOGGER_FILE';
    const LOGGER_DB = 'PEACHCOMMERCE_LOGGER_DB';
    const WALLET_PREFIX = 'lightning:';
    const GUIDE_LINK = 'https://github.com/LightningPeach/peach_commerce_prestashop/blob/master/README.md';
    const GITHUB_LINK = 'https://github.com/LightningPeach/peach_commerce_prestashop';
    const WALLET_LINK = 'https://lightningpeach.com/peach-wallet';
    const HUB_CHANNEL_LINK = 'https://lightningpeach.com/peach-public-node';
    const LOGGER_FILENAME_POSTFIX = '_peachcommerce.log';

    const FORM_NOTIFICATION_URL = 'FORM_NOTIFICATION_URL';

    private $postErrors = array();
    private $tabName = 'AdminPeachCommerce';
    private $hubHost;
    private $merchantId;

    public $logger = null;

    public $api;

    public function __construct()
    {
        $name = PeachCommerce::NAME;
        $this->name = $name;
        $this->tab = 'payments_gateways';
        $this->version = '0.1.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'LightningPeach';
        $this->need_instance = 1;
        $this->is_eu_compatible = 1;
        $this->bootstrap = true;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->logger = new PeachCommerceLogger();
        $this->logger->setLogger();

        $config = Configuration::getMultiple(array(self::HOST, self::MERCHANT_ID));
        if (!empty($config[self::HOST])) {
            $this->hubHost = $config[self::HOST];
        }
        if (!empty($config[self::MERCHANT_ID])) {
            $this->merchantId = $config[self::MERCHANT_ID];
        }

        parent::__construct();

        $this->displayName = $this->l('Peach Commerce');
        $this->description = $this->l('Accept payments by Lightning.');

        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (empty($this->hubHost) || empty($this->merchantId)) {
            $this->warning = $this->l(
                'Hub host and merchant ID must be configured before using this module'
            );
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
        $this->api = new Hub\LightningClient($this->hubHost, $this->merchantId);
    }

    public function maxPayment()
    {
        $miliSatoshi = 1e-3;
        return round(pow(2, 32) * $miliSatoshi);
    }

    public function satoshiToBtc($value)
    {
        return $value * 1e-8;
    }

    /**
     * Hub host
     *
     * @return string|null
     */
    public function getHubHost()
    {
        return $this->hubHost;
    }

    /**
     * Merchant id
     *
     * @return string|null
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * Is called in module install process
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');

            return false;
        }

        return parent::install() &&
            PeachCommerceSql::install() &&
            $this->installTab() &&
            $this->installOrderStatus() &&
            /** @see PeachCommerce::hookDisplayOrderDetail */
            $this->registerHook('displayOrderDetail') &&
            /** @see PeachCommerce::hookHeader */
            $this->registerHook('header') &&
            /** @see PeachCommerce::hookBackOfficeHeader */
            $this->registerHook('backOfficeHeader') &&
            /** @see PeachCommerce::hookPaymentReturn */
            $this->registerHook('paymentReturn') &&
            /** @see PeachCommerce::hookPaymentOptions */
            $this->registerHook('paymentOptions') &&
            /** @see PeachCommerce::hookDisplayAdminOrderContentOrder */
            $this->registerHook('displayAdminOrderContentOrder');
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $this->tabName;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Peach Commerce';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentPayment');

        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * Add custom pending order status
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
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

    /**
     * Is called in uninstall module process
     *
     * @return bool
     */
    public function uninstall()
    {
        return Configuration::deleteByName(self::HOST) &&
            Configuration::deleteByName(self::MERCHANT_ID) &&
            $this->uninstallTab() &&
            parent::uninstall();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName($this->tabName);
        $tab = new Tab((int)$id_tab);
        return $tab->delete();
    }

    /**
     * Load the data for module configuration page
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getContent()
    {
        $html = '';
        if (((bool)Tools::isSubmit('submitPeachCommerceModule')) == true) {
            $this->postProcess();
            if (count($this->postErrors)) {
                foreach ($this->postErrors as $err) {
                    $html .= $this->displayError($err);
                }
                $html .= '<br/>';
            }
        }

        $moduleLink = Tools::getProtocol(Tools::usingSecureMode()) . $_SERVER['HTTP_HOST'] . $this->getPathUri();
        $this->context->smarty->assign(array(
            'cronLink' =>
                $moduleLink . 'cron.php' . '?token=' . Tools::substr(Tools::hash('peachCommerce/cron'), 0, 10),
            'guideLink' => self::GUIDE_LINK,
            'githubLink' => self::GITHUB_LINK,
        ));
        $html .= $this->display(__FILE__, 'backend_settings.tpl');
        $html .= $this->renderForm();

        return $html;
    }

    /**
     * Render config form in module configuration page
     *
     * @see PeachCommerce::getContent
     * @return string
     * @throws PrestaShopException
     */
    protected function renderForm()
    {
        /** @var HelperFormCore $helper */
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPeachCommerceModule';
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

    /**
     * Return values for config form
     *
     * @see PeachCommerce::renderForm
     * @return array
     */
    protected function getConfigFormValues()
    {
        return array(
            self::HOST => Configuration::get(self::HOST, null),
            self::MERCHANT_ID => Configuration::get(self::MERCHANT_ID, null),
            self::FORM_NOTIFICATION_URL => $this->context->link->getModuleLink(
                $this->name,
                'notification',
                array(),
                true
            ),
            self::LOGGER_TYPE => Configuration::get(self::LOGGER_TYPE, null),
        );
    }

    /**
     * Return form structure for module config page
     *
     * @see PeachCommerce::renderForm
     * @return array
     */
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
                        'col' => 5,
                        'type' => 'text',
                        'desc' => $this->l('the URL for hub API, is provided by Peach team.'),
                        'name' => self::HOST,
                        'label' => $this->l('Hub host'),
                        'required' => true,
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'desc' => $this->l('a secret key to your account on Peach Public Node, is provided by Peach team.'),
                        'name' => self::MERCHANT_ID,
                        'label' => $this->l('Merchant ID'),
                        'required' => true,
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'desc' => $this->l('a URL for webhook to inform you about a new successful payment. Should be provided to Peach team to create an account.'),
                        'name' => self::FORM_NOTIFICATION_URL,
                        'label' => $this->l('Notification URL'),
                        'readonly' => true,
                    ),
                    array(
                        'col' => 5,
                        'type' => 'select',
                        'desc' => $this->l('Module logger type.'),
                        'name' => self::LOGGER_TYPE,
                        'label' => $this->l('Logger type'),
                        'options' => array(
                            'query' => $options = array(
                                array(
                                    'id_option' => null,
                                    'name' => 'Disable module logger',
                                ),
                                array(
                                    'id_option' => self::LOGGER_FILE,
                                    'name' => 'File',
                                ),
                                array(
                                    'id_option' => self::LOGGER_DB,
                                    'name' => 'Database',
                                ),
                            ),
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Handle post action for module config form
     */
    protected function postProcess()
    {
        Configuration::updateValue(self::LOGGER_TYPE, Tools::getValue(self::LOGGER_TYPE));

        $host = Tools::getValue(self::HOST);
        $merchantId = Tools::getValue(self::MERCHANT_ID);

        if (!$host) {
            $this->postErrors[] = $this->l('Hub host is required.');
        } elseif (!filter_var($host, FILTER_VALIDATE_URL)) {
            $this->postErrors[] = $this->l('Hub host is not url.');
        }
        if (!$merchantId) {
            $this->postErrors[] = $this->l('Merchant id is required.');
        }
        if (count($this->postErrors)) {
            $this->logger->logError('PeachCommerce->postProcess', $this->postErrors);
            return;
        }
        $testApi = new Hub\LightningClient($host, $merchantId);
        try {
            $testApi->getBalance();
            Configuration::updateValue(self::HOST, $host);
            Configuration::updateValue(self::MERCHANT_ID, $merchantId);
        } catch (Hub\LightningException $error) {
            $this->logger->logError(
                'PeachCommerce->postProcess: Api getBalance exception',
                array('message' => $error->getMessage())
            );
            $this->postErrors[] = $this->l('Hub host is invalid');
        }
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     */
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

    public function addOrderInfo($orderId, $invoice)
    {
        $order = new PeachCommerceSql();
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
        $value = rtrim(rtrim($value, '0'), '.') . ' BTC';

        return $value;
    }

    /** HOOKS SECTION */

    /**
     * This hook is used to display info in order detail (history) page
     *
     * @param array $params
     *
     * @return null|string
     * @throws PrestaShopException
     */
    public function hookDisplayOrderDetail($params)
    {
        /** @var Order $order */
        $order = $params['order'];
        $orderInfo = PeachCommerceSql::loadByOrderId($order->id);
        if (!$orderInfo->order_id || (int)$orderInfo->order_id !== $order->id) {
            $this->logger->logError(
                'PeachCommerce->hookDisplayOrderDetail: OrderId not found',
                array('order' => $order)
            );
            return null;
        }
        $waiting = $order->getCurrentOrderState()->id === (int)Configuration::get(self::OS_WAITING);

        $now = new \DateTime();
        $expiryAt = (int)$orderInfo->creation_time + (int)$orderInfo->expiry;
        if ($expiryAt <= $now->getTimestamp() && $waiting) {
            $this->logger->logDebug(
                'PeachCommerce->hookDisplayOrderDetail: Order expired',
                array('orderInfo' => $orderInfo, 'order' => $order, 'checkTime' => $now->getTimestamp())
            );
            $order->setCurrentState((int)Configuration::get('PS_OS_CANCELED'));
            $order->save();
            Tools::redirect($_SERVER['REQUEST_URI']);
            die();
        }

        $this->context->smarty->assign('payReq', $orderInfo->payment_request);
        $this->context->smarty->assign('expiry_at', $expiryAt);
        $html = $this->display(__FILE__, 'frontend_order_info.tpl');

        return $html;
    }

    /**
     * Add custom css/js for front office (userview) header
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addJS($this->_path . '/views/js/libs/jquery-qrcode/jquery.qrcode.min.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * Add custom css/js for back office (admin) header
     */
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    }

    /**
     * This hook is used to display the order confirmation page.
     *
     * @param array $params
     *
     * @return mixed|null
     * @throws PrestaShopException
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return null;
        }
        /** @var Order $order */
        $order = $params['order'];
        $currency = new Currency($params['order']->id_currency);

        $totalPaid = $order->getOrdersTotalPaid();

        try {
            $BTC = $this->api->getCurrency(Tools::strtolower($currency->iso_code), $totalPaid);
            $BTC .= '  BTC';
        } catch (Hub\LightningException $e) {
            $this->logger->logError(
                'PeachCommerce->hookPaymentReturn: Api getCurrency exception',
                array($e->getMessage())
            );
            $BTC = $e->getMessage();
        }

        $orderObj = PeachCommerceSql::loadByOrderId($order->id);
        $payReq = $orderObj->payment_request;
        $expiryAt = $orderObj->creation_time + $orderObj->expiry;

        $walletBtn = self::WALLET_PREFIX . $payReq;

        $waiting = $order->getCurrentOrderState()->id === (int)Configuration::get(self::OS_WAITING);
        $now = new \DateTime();
        $canceled = $expiryAt <= $now->getTimestamp();
        if ($canceled && $waiting) {
            $order->setCurrentState((int)Configuration::get('PS_OS_CANCELED'));
            $order->save();
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
                'status_link' => $this->context->link->getModuleLink($this->name, 'orderstatus', array(), true),
            )
        );

        return $this->fetch('module:peachcommerce/views/templates/hook/order_complete.tpl');
    }

    /**
     * This hook is used to display module payment options in checkout page
     *
     * @param array $params
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return null;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }

        if (!$this->merchantId || !$this->hubHost) {
            return null;
        }

        $this->smarty->assign('walletLink', self::WALLET_LINK);
        $this->smarty->assign('hubChannel', self::HUB_CHANNEL_LINK);
        $newOption = new PaymentOption();
        $newOption
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/option_logo.png'))
            ->setCallToActionText($this->l('Pay by Lightning'))
            ->setModuleName($this->name)
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation(
                $this->fetch('module:peachcommerce/views/templates/front/payment_info.tpl')
            );
        $payment_options = array($newOption);

        return $payment_options;
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        /** @var Order $order */
        $order = $params['order'];
        $orderInfo = PeachCommerceSql::loadByOrderId($order->id);
        if (!$orderInfo->order_id || (int)$orderInfo->order_id !== $order->id) {
            return null;
        }
        $waiting = $order->getCurrentOrderState()->id === (int)Configuration::get(self::OS_WAITING);

        $now = new \DateTime();
        $expiryAt = (int)$orderInfo->creation_time + (int)$orderInfo->expiry;
        if ($expiryAt <= $now->getTimestamp() && $waiting) {
            $this->logger->logDebug(
                'PeachCommerce->hookDisplayAdminOrderContentOrder: Order expired',
                array('orderInfo' => $orderInfo, 'order' => $order, 'checkTime' => $now->getTimestamp())
            );
            $order->setCurrentState((int)Configuration::get('PS_OS_CANCELED'));
            $order->save();
            Tools::redirect($_SERVER['REQUEST_URI']);
            die();
        }

        $this->context->smarty->assign('payReq', $orderInfo->payment_request);
        $html = $this->display(__FILE__, 'backend_order_info.tpl');

        return $html;
    }
}
