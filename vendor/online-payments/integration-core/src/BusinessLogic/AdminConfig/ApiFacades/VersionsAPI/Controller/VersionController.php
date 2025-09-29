<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\VersionsAPI\Controller;

use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\VersionsAPI\Response\VersionInfoResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
/**
 * Class VersionController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\VersionsAPI\Controller
 */
class VersionController
{
    protected VersionService $service;
    /**
     * @param VersionService $service
     */
    public function __construct(VersionService $service)
    {
        $this->service = $service;
    }
    /**
     * Retrieves plugin current and latest version.
     *
     * @return VersionInfoResponse
     */
    public function getVersionInfo() : VersionInfoResponse
    {
        return new VersionInfoResponse($this->service->getVersionInfo());
    }
}
