<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Response;

use OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;

/**
 * Class ConnectionResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Response
 */
class ConnectionResponse extends Response
{
    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [];
    }
}
