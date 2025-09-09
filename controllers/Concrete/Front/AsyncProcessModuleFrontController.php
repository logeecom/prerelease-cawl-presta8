<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Front;

use ModuleFrontController;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
/**
 * Class AsyncProcessModuleFrontController.
 *
 * @package OnlinePayments\Controllers\Concrete\Front
 */
class AsyncProcessModuleFrontController extends ModuleFrontController
{
    public function initContent() : void
    {
        $this->respondOK();
        $guid = \trim(\Tools::getValue('guid'));
        if ($guid !== 'auto-configure') {
            /** @var AsyncProcessService $asyncProcessService */
            $asyncProcessService = ServiceRegister::getService(AsyncProcessService::class);
            $asyncProcessService->runProcess($guid);
        }
        die(\json_encode(['success' => \true]));
    }
    public function respondOK() : void
    {
        // check if fastcgi_finish_request is callable
        if (\function_exists('fastcgi_finish_request') && \is_callable('fastcgi_finish_request')) {
            /*
             * This works in Nginx but the next approach not
             */
            \session_write_close();
            \fastcgi_finish_request();
            return;
        }
        if (\function_exists('litespeed_finish_request') && \is_callable('litespeed_finish_request')) {
            \session_write_close();
            litespeed_finish_request();
            return;
        }
        \ignore_user_abort(\true);
        \ob_start();
        \header('HTTP/1.1 204 No Content');
        \header('Content-Encoding: none');
        \header('Content-Length: 0');
        \header('Connection: close');
        \ob_end_flush();
        \ob_flush();
        \flush();
    }
}
