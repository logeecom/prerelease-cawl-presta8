<?php

namespace OnlinePayments\Classes\Repositories;

use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

/**
 * Class TokensRepository
 *
 * @package OnlinePayments\Classes\Repositories
 */
class TokensRepository extends BaseRepositoryWithConditionalDelete
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;

    public const TABLE_NAME = 'tokens';

    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName(): string
    {
        return self::getFullTableName();
    }

    public static function getFullTableName(): string
    {
        /** @var ActiveBrandProviderInterface $provider */
        $provider = ServiceRegister::getService(ActiveBrandProviderInterface::class);

        return strtolower($provider->getActiveBrand()->getCode()) . '_' . self::TABLE_NAME;
    }
}
