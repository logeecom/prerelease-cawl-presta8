<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Exceptions;

use Throwable;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
/**
 * Class InvalidTranslatableArrayException.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Translations\Exceptions
 * @internal
 */
class InvalidTranslatableArrayException extends BaseTranslatableException
{
    /**
     * @param TranslatableLabel $translatableLabel
     * @param Throwable|null $previous
     */
    public function __construct(TranslatableLabel $translatableLabel, Throwable $previous = null)
    {
        $this->code = 404;
        parent::__construct($translatableLabel, $previous);
    }
}
