<?php

namespace OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\ExemptionType;
use OnlinePayments\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use OnlinePayments\Core\Infrastructure\ORM\Configuration\IndexMap;
use OnlinePayments\Core\Infrastructure\ORM\Entity;

/**
 * Class CardsSettingsEntity
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings
 */
class CardsSettingsEntity extends Entity
{
    public const CLASS_NAME = __CLASS__;
    protected string $storeId;
    protected string $mode;
    protected CardsSettings $cardsSettings;

    /**
     * @inheritDoc
     */
    public function getConfig(): EntityConfiguration
    {
        $indexMap = new IndexMap();

        $indexMap->addStringIndex('storeId');
        $indexMap->addStringIndex('mode');

        return new EntityConfiguration($indexMap, 'CardsSettings');
    }

    public function inflate(array $data): void
    {
        parent::inflate($data);

        $this->storeId = $data['storeId'];
        $this->mode = $data['mode'];
        $cardsSettings = $data['cardsSettings'];
        $this->cardsSettings = new CardsSettings(
            $cardsSettings['enable3ds'],
            $cardsSettings['enforceStrongAuthentication'],
            $cardsSettings['enable3dsExemption'],
            !empty($cardsSettings['exemptionType']) ?
                ExemptionType::fromState($cardsSettings['exemptionType']) : null,
            !empty($cardsSettings['exemptionLimit']) ? Amount::fromArray($cardsSettings['exemptionLimit']) : null,
        );
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['storeId'] = $this->storeId;
        $data['mode'] = $this->mode;
        $data['cardsSettings'] = [
            'enable3ds' => $this->cardsSettings->isEnable3ds(),
            'enforceStrongAuthentication' => $this->cardsSettings->isEnforceStrongAuthentication(),
            'enable3dsExemption' => $this->cardsSettings->isEnable3dsExemption(),
            'exemptionType' => $this->cardsSettings->getExemptionType() ?
                $this->cardsSettings->getExemptionType()->getType() : '',
            'exemptionLimit' => $this->cardsSettings->getExemptionLimit() ?
                $this->cardsSettings->getExemptionLimit()->toArray() : '',
        ];

        return $data;
    }

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    public function setStoreId(string $storeId): void
    {
        $this->storeId = $storeId;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getCardsSettings(): CardsSettings
    {
        return $this->cardsSettings;
    }

    public function setCardsSettings(CardsSettings $cardsSettings): void
    {
        $this->cardsSettings = $cardsSettings;
    }
}
