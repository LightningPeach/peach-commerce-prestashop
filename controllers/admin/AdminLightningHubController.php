<?php

class AdminLightningHubController extends ModuleAdminController
{
    /** @var LightningHub $module */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Administration');
        $this->toolbar_title[] = $this->l('Lightning Hub');
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
            )
        ));
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'lightninghub/views/templates/back/balance.tpl');
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
                    'tx_hash' => $withdraw->tx,
                    'balance' => $this->module->formatBTC($this->module->convertToBTCFromSatoshi($balance))
                )
            ));
        } catch (\Exception $e) {
            die(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }
}
