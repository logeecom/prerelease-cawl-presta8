<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Exceptions;

use Throwable;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
/**
 * Class BaseTranslatableUnhandledException
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Translations\Model
 * @internal
 */
class BaseTranslatableUnhandledException extends BaseTranslatableException
{
    /**
     * @param Throwable $previous
     */
    public function __construct(Throwable $previous)
    {
        parent::__construct(new TranslatableLabel('Unhandled error occurred: ' . $previous->getMessage(), 'general.unhandled'), $previous);
    }
}
