<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\Exceptions;

use Exception;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Exceptions\BaseTranslatableException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
/**
 * Class FailedToRetrieveOrderStatusesException
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Stores\Exceptions
 * @internal
 */
class FailedToRetrieveOrderStatusesException extends BaseTranslatableException
{
    public function __construct(Exception $previous)
    {
        parent::__construct(new TranslatableLabel('Failed to retrieve order statuses.', 'general.failedToRetrieveOrderStatuses'), $previous);
    }
}
