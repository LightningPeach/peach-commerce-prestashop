<?php

namespace LightningHub\Hub;

require(__DIR__ . DIRECTORY_SEPARATOR . 'LightningException.php');
require(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
require(_PS_MODULE_DIR_ . DIRECTORY_SEPARATOR . 'peachcommerce' . DIRECTORY_SEPARATOR . 'logger.php');

use RestClient;

class LightningClient
{
    const PREFIX = 'api/v1/';

    private $api;
    private $info;
    private $logger;

    public function __construct($url = null, $merchant_id = null)
    {
        $this->logger = new \PeachCommerceLogger();
        $this->logger->setLogger();
        if ($url && $merchant_id) {
            $this->api = new RestClient(
                [
                    'base_url' => rtrim($url, '/'),
                    'curl_options' => [
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_USERPWD => $merchant_id . ":",
                    ],
                ]
            );
        }
        $this->info = new RestClient(
            [
                'base_url' => 'https://blockchain.info/',
            ]
        );
    }

    public function getCurrency($currency, $amount)
    {
        $res = $this->info->get(
            'tobtc',
            [
                'currency' => $currency,
                'value' => $amount,
            ]
        );
        if ($res->info->http_code !== 200) {
            throw new LightningException($res->error);
        }

        return (float)$res->response;
    }

    public function satoshiToBtc($amount)
    {
        return (int)($amount * 1e8);
    }

    public function invoice($props)
    {
        $value = (float)$this->getCurrency($props['currency'], $props['amount']);
        $params = [
            'amount' => $this->satoshiToBtc($value),
            'description' => $props['memo'],
        ];
        return $this->callApi(
            'post',
            "payment",
            json_encode($params),
            ['Content-Type' => 'application/json']
        )->data;
    }

    public function fetch($r_hash)
    {
        $url = 'payments/' . $r_hash;
        return $this->callApi('get', $url)->data;
    }

    public function fetchAll()
    {
        return $this->callApi('get', 'payments')->data;
    }

    public function getBalance()
    {
        return $this->callApi('get', 'balance')->data;
    }

    public function withdraw()
    {
        return $this->callApi('post', 'withdraw');
    }

    private function getResponseError($response)
    {
        $baseError = $response->error;
        $baseResponse = $response->response;
        try {
            $data = json_decode($baseResponse);
            if (isset($data->err)) {
                return $data->err;
            }
            if (isset($data->error)) {
                return $data->error;
            }
            return $baseError;
        } catch (\Exception $error) {
            return $baseError;
        }
    }

    private function callApi($method, $url, $params = [], $headers = [])
    {
        if (!$this->api) {
            throw new LightningException('Api not configured');
        }
        $this->logger->logDebug(
            'Will send request to ' . self::PREFIX . $url,
            array(
                'Params' => $params,
            )
        );
        $res = $this->api->{$method}(self::PREFIX . $url, $params, $headers);
        $this->logger->logDebug(
            'Got response from ' . self::PREFIX . $url,
            array(
                'Info' => $res->info,
                'Response' => $res->response,
            )
        );
        if ($res->info->http_code !== 200) {
            throw new LightningException($this->getResponseError($res));
        }
        $data = json_decode($res->response);

        return $data;
    }
}