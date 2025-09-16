<?php

namespace CAWL\OnlinePayments\Classes\Services;

use Module;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class ImageHandler
 *
 * @package OnlinePayments\Classes\Services
 */
class ImageHandler
{
    /**
     * @param string $file
     * @param string $fileName
     * @param string $storeId
     * @param string $mode
     *
     * @return bool
     */
    public static function saveImage(string $file, string $fileName, string $storeId, string $mode) : bool
    {
        /** @var Module $module */
        $module = ServiceRegister::getService(Module::class);
        if (!\file_exists(\_PS_IMG_DIR_ . $module->name)) {
            \mkdir(\_PS_IMG_DIR_ . $module->name);
        }
        if (!\file_exists(\_PS_IMG_DIR_ . $module->name . '/' . $storeId)) {
            \mkdir(\_PS_IMG_DIR_ . $module->name . '/' . $storeId);
        }
        if (!\file_exists(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode)) {
            \mkdir(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode);
        }
        return \move_uploaded_file($file, \_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode . '/' . $fileName . '.png');
    }
    public static function copyHostedCheckoutDefaultImage(string $path, string $storeId, string $mode) : bool
    {
        /** @var Module $module */
        $module = ServiceRegister::getService(Module::class);
        if (!\file_exists(\_PS_IMG_DIR_ . $module->name)) {
            \mkdir(\_PS_IMG_DIR_ . $module->name);
        }
        if (!\file_exists(\_PS_IMG_DIR_ . $module->name . '/' . $storeId)) {
            \mkdir(\_PS_IMG_DIR_ . $module->name . '/' . $storeId);
        }
        if (!\file_exists(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode)) {
            \mkdir(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode);
        }
        return \copy($path, \_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode . '/hosted_checkout.svg');
    }
    /**
     * @param string $fileName
     * @param string $storeId
     * @param string $mode
     *
     * @return string
     */
    public static function getImageUrl(string $fileName, string $storeId, string $mode) : string
    {
        /** @var Module $module */
        $module = ServiceRegister::getService(Module::class);
        $shop = new \Shop($storeId);
        if (!\file_exists(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode . '/' . $fileName . '.png')) {
            return '';
        }
        return $shop->getBaseURL() . 'img/' . $module->name . '/' . $storeId . '/' . $mode . '/' . $fileName . '.png';
    }
    /**
     * @param string $fileName
     * @param string $storeId
     * @param string $mode
     *
     * @return void
     */
    public static function removeImage(string $fileName, string $storeId, string $mode) : void
    {
        $module = ServiceRegister::getService(Module::class);
        if (\file_exists(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode . '/' . $fileName . '.png')) {
            \unlink(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode . '/' . $fileName . '.png');
        }
    }
    /**
     * @param string $storeId
     * @param string $mode
     *
     * @return void
     */
    public static function removeDirectoryForStore(string $storeId, string $mode) : void
    {
        $module = ServiceRegister::getService(Module::class);
        if (\file_exists(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode)) {
            $items = \array_diff(\scandir(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode), ['.', '..']);
            foreach ($items as $item) {
                \unlink(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode . '/' . $item);
            }
            \rmdir(\_PS_IMG_DIR_ . $module->name . '/' . $storeId . '/' . $mode);
        }
    }
    /**
     * @return void
     */
    public static function removeOnlinePaymentsDirectory() : void
    {
        $module = ServiceRegister::getService(Module::class);
        if (\file_exists(\_PS_IMG_DIR_ . $module->name)) {
            \rmdir(\_PS_IMG_DIR_ . $module->name);
        }
    }
}
