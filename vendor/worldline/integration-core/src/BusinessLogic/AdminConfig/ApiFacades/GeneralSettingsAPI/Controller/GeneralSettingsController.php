<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Controller;

use Exception;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\CardsSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\LogSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PayByLinkSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PaymentSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Response\DisconnectResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Response\GeneralSettingsResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Response\SaveSettingsResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect\DisconnectService;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\GeneralSettingsService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Exceptions\InvalidCurrencyCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidActionTypeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidAutomaticCaptureValueException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidExemptionTypeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidLogRecordsLifetimeException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidPaymentAttemptsNumberException;
/**
 * Class GeneralSettingsController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Controller
 * @internal
 */
class GeneralSettingsController
{
    protected GeneralSettingsService $generalSettingsService;
    protected DisconnectService $disconnectService;
    /**
     * @param GeneralSettingsService $generalSettingsService
     * @param DisconnectService $disconnectService
     */
    public function __construct(GeneralSettingsService $generalSettingsService, DisconnectService $disconnectService)
    {
        $this->generalSettingsService = $generalSettingsService;
        $this->disconnectService = $disconnectService;
    }
    /**
     * @return GeneralSettingsResponse
     *
     * @throws InvalidAutomaticCaptureValueException
     * @throws InvalidLogRecordsLifetimeException
     * @throws InvalidPaymentAttemptsNumberException
     */
    public function getGeneralSettings() : GeneralSettingsResponse
    {
        return new GeneralSettingsResponse($this->generalSettingsService->getGeneralSettings());
    }
    /**
     * @param CardsSettingsRequest $request
     *
     * @return SaveSettingsResponse
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidExemptionTypeException
     */
    public function saveCardsSettings(CardsSettingsRequest $request) : SaveSettingsResponse
    {
        $this->generalSettingsService->saveCardsSettings($request->transformToDomainModel());
        return new SaveSettingsResponse();
    }
    /**
     * @param PaymentSettingsRequest $request
     *
     * @return SaveSettingsResponse
     *
     * @throws InvalidAutomaticCaptureValueException
     * @throws InvalidPaymentAttemptsNumberException
     * @throws InvalidActionTypeException
     */
    public function savePaymentSettings(PaymentSettingsRequest $request) : SaveSettingsResponse
    {
        $this->generalSettingsService->savePaymentSettings($request->transformToDomainModel());
        return new SaveSettingsResponse();
    }
    /**
     * @param LogSettingsRequest $request
     *
     * @return SaveSettingsResponse
     *
     * @throws InvalidLogRecordsLifetimeException
     */
    public function saveLogSettings(LogSettingsRequest $request) : SaveSettingsResponse
    {
        $this->generalSettingsService->saveLogSettings($request->transformToDomainModel());
        return new SaveSettingsResponse();
    }
    /**
     * @param PayByLinkSettingsRequest $request
     *
     * @return SaveSettingsResponse
     */
    public function savePayByLinkSettings(PayByLinkSettingsRequest $request) : SaveSettingsResponse
    {
        $this->generalSettingsService->savePayByLinkSettings($request->transformToDomainModel());
        return new SaveSettingsResponse();
    }
    /**
     * @return DisconnectResponse
     *
     * @throws Exception
     */
    public function disconnect() : DisconnectResponse
    {
        $this->disconnectService->disconnect();
        return new DisconnectResponse();
    }
}
