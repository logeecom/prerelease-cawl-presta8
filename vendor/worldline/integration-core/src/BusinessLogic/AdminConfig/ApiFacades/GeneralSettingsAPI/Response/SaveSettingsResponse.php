<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Response;

use OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;

/**
 * Class SaveSettingsResponse
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Response
 */
class SaveSettingsResponse extends Response
{
    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [];
    }
}
