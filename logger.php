<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class PeachCommerceLogger
{
    private $logger;

    public function setLogger()
    {
        $loggerType = Configuration::get(PeachCommerce::LOGGER_TYPE);
        if ($loggerType === PeachCommerce::LOGGER_FILE) {
            $this->logger = new FileLogger(0);
            $this->logger->setFilename(_PS_ROOT_DIR_ . '/var/logs/' . date('Ymd') . PeachCommerce::LOGGER_FILENAME_POSTFIX);
        }
    }

    private function log($message, $level, $params = array())
    {
        $loggerType = Configuration::get(PeachCommerce::LOGGER_TYPE);
        if (empty($loggerType)) {
            return;
        }
        $msg = $message;
        if (!empty($params)) {
            if (is_string($params)) {
                $msg .= ': ' . $params;
            } else {
                $msg .= ': ' . json_encode($params);
            }
        }
        if ($loggerType === PeachCommerce::LOGGER_DB) {
            PrestaShopLogger::addLog($msg, $level, null, 'PeachCommerce', 1);
        }
        if ($loggerType === PeachCommerce::LOGGER_FILE && !is_null($this->logger)) {
            $method = null;
            switch ($level) {
                case AbstractLoggerCore::ERROR:
                    $this->logger->logError($msg);
                    break;
                case AbstractLoggerCore::WARNING:
                    $this->logger->logWarning($msg);
                    break;
                case AbstractLoggerCore::INFO:
                    $this->logger->logInfo($msg);
                    break;
                case AbstractLoggerCore::DEBUG:
                default:
                    $this->logger->logDebug($msg);
                    break;
            }
        }
    }

    public function logError($message, $params = array())
    {
        $this->log($message, AbstractLoggerCore::ERROR, $params);
    }

    public function logWarning($message, $params = array())
    {
        $this->log($message, AbstractLoggerCore::WARNING, $params);
    }

    public function logInfo($message, $params = array())
    {
        $this->log($message, AbstractLoggerCore::INFO, $params);
    }

    public function logDebug($message, $params = array())
    {
        $this->log($message, AbstractLoggerCore::DEBUG, $params);
    }
}
