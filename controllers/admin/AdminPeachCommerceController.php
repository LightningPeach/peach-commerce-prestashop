<?php

class AdminPeachCommerceController extends ModuleAdminController
{
    /** @var PeachCommerce $module */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
        if (!$this->module->active) {
            $this->module->logDebug(
                'AdminPeachCommerceController->__construct: Module not active',
                'Redirect to AdminHome page'
            );
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        } elseif (!$this->module->getHubHost() || !$this->module->getMerchantId()) {
            $this->module->logDebug(
                'AdminPeachCommerceController->__construct: Hub host or merchantId not set',
                'Redirect to config page'
            );
            Tools::redirectAdmin(
                $this->context->link->getAdminLink(
                    'AdminModules',
                    true,
                    array(),
                    array('configure' => $this->module->name)
                )
            );
        }
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Administration');
        $this->toolbar_title[] = $this->l('Peach Commerce');
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['back']);
    }

    public function initContent()
    {
        try {
            $balanceReq = $this->module->api->getBalance();
        } catch (\Exception $error) {
            $this->module->logError(
                'AdminPeachCommerceController->initContent: Api getBalance exception',
                array('message' => $error->getMessage())
            );
            $balanceReq = null;
        }
        if (!$balanceReq) {
            $balance = 0;
        } else {
            $balance = $balanceReq->balance;
        }

        parent::initContent();
        $html = $this->content;
        $this->context->smarty->assign(array(
            'balance' => $this->module->formatBTC(
                $this->module->convertToBTCFromSatoshi($balance)
            ),
        ));
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'peachcommerce/views/templates/admin/balance.tpl');
        $this->context->smarty->assign(array(
            'content' => $html,
        ));
    }

    public function ajaxProcessWithDraw()
    {
        try {
            $withdraw = $this->module->api->withdraw();
            if (isset($withdraw->err) && $withdraw->err) {
                die(json_encode(array('ok' => false, 'error' => $withdraw->err)));
            }

            try {
                $balanceReq = $this->module->api->getBalance();
            } catch (\Exception $error) {
                $this->module->logError(
                    'AdminPeachCommerceController->ajaxProcessWithDraw: Api getBalance exception',
                    array('message' => $error->getMessage())
                );
                $balanceReq = null;
            }
            if (!$balanceReq) {
                $balance = 0;
            } else {
                $balance = $balanceReq->balance;
            }
            die(json_encode(
                array(
                    'ok' => true,
                    'tx_hash' => $withdraw->data->tx,
                    'balance' => $this->module->formatBTC($this->module->convertToBTCFromSatoshi($balance)),
                )
            ));
        } catch (\Exception $error) {
            $this->module->logError(
                'AdminPeachCommerceController->ajaxProcessWithDraw: Exception',
                array('message' => $error->getMessage())
            );
            die(json_encode(array('ok' => false, 'error' => $error->getMessage())));
        }
    }
}
