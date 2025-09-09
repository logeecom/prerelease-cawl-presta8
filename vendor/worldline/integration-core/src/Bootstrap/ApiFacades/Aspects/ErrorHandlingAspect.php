<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Aspects;

use Exception;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Response\TranslatableErrorResponse;
use CAWL\OnlinePayments\Core\Bootstrap\Aspect\Aspect;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Exceptions\BaseTranslatableException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Exceptions\BaseTranslatableUnhandledException;
use Throwable;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
/**
 * Class ErrorHandlingAspect.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Aspects
 * @internal
 */
class ErrorHandlingAspect implements Aspect
{
    /**
     * @throws Exception
     */
    public function applyOn($callee, array $params = [])
    {
        try {
            $response = \call_user_func_array($callee, $params);
        } catch (BaseTranslatableException $e) {
            Logger::logError($e->getMessage(), 'Core', ['message' => $e->getMessage(), 'type' => \get_class($e), 'trace' => $e->getTraceAsString()]);
            $response = TranslatableErrorResponse::fromError($e);
        } catch (Throwable $e) {
            Logger::logError('Unhandled error occurred.', 'Core', ['message' => $e->getMessage(), 'type' => \get_class($e), 'trace' => $e->getTraceAsString()]);
            $exception = new BaseTranslatableUnhandledException($e);
            $response = TranslatableErrorResponse::fromError($exception);
        }
        return $response;
    }
}
