<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration\Logger;

use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\LogData;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class LoggerService
 *
 * @package OnlinePayments\Classes\Services\Integration\Logger
 */
class LoggerService implements ShopLoggerAdapter
{
    private OnlinePaymentsModule $module;
    public function __construct(OnlinePaymentsModule $module)
    {
        $this->module = $module;
    }
    /**
     * PrestaShop log severity level codes.
     */
    private const PRESTASHOP_INFO = 1;
    private const PRESTASHOP_WARNING = 2;
    private const PRESTASHOP_ERROR = 3;
    /**
     * Log level names for corresponding log level codes.
     *
     * @var array
     */
    private static array $logLevelName = [Logger::ERROR => 'ERROR', Logger::WARNING => 'WARNING', Logger::INFO => 'INFO', Logger::DEBUG => 'DEBUG'];
    /**
     * Mappings of Online Payments log severity levels to PrestaShop log severity levels.
     *
     * @var array
     */
    private static array $logMapping = [Logger::ERROR => self::PRESTASHOP_ERROR, Logger::WARNING => self::PRESTASHOP_WARNING, Logger::INFO => self::PRESTASHOP_INFO, Logger::DEBUG => self::PRESTASHOP_INFO];
    /**
     * @inheritDoc
     */
    public function logMessage(LogData $data) : void
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $minLogLevel = $configService->getMinLogLevel();
        $logLevel = $data->getLogLevel();
        if ($logLevel > $minLogLevel && !$configService->isDebugModeEnabled()) {
            return;
        }
        $message = $this->module->getBrand()->getCode() . ' LOG:' . ' | ' . 'Date: ' . \date('d/m/Y') . ' | ' . 'Time: ' . \date('H:i:s') . ' | ' . 'Log level: ' . self::$logLevelName[$logLevel] . ' | ' . 'Message: ' . $data->getMessage();
        $context = $data->getContext();
        if (!empty($context)) {
            $contextData = [];
            foreach ($context as $item) {
                $contextData[$item->getName()] = \print_r($item->getValue(), \true);
            }
            $message .= ' | ' . 'Content data: [' . \json_encode($contextData) . ']';
        }
        \PrestaShopLogger::addLog($message, self::$logMapping[$logLevel]);
        $logger = new \FileLogger();
        $logger->setFilename($this->module->getLocalPath() . '/logs/' . \date('Ymd', \time()) . $this->module->name . '.log');
        $logger->log($message, self::$logMapping[$logLevel]);
        $this->removeOldLogFiles();
    }
    private function removeOldLogFiles() : void
    {
        $notExpiredLogs = [\date('Ymd', \time() - 172800) . $this->module->name . '.log', \date('Ymd', \time() - 86400) . $this->module->name . '.log', \date('Ymd') . $this->module->name . '.log'];
        $files = \array_diff(\scandir($this->module->getLocalPath() . '/logs/'), array('.', '..'));
        if (\count($files) < 4) {
            return;
        }
        foreach ($files as $file) {
            if (\is_file($this->module->getLocalPath() . '/logs/' . $file) && !\in_array($file, $notExpiredLogs)) {
                \unlink($this->module->getLocalPath() . '/logs/' . $file);
            }
        }
    }
}
