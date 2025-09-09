<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Encryption\Encryptor as EncryptorInterface;
use PhpEncryption;
/**
 * Class Encryptor
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class Encryptor implements EncryptorInterface
{
    /**
     * @inheritDoc
     */
    public function encrypt(string $data) : string
    {
        return (new PhpEncryption(_NEW_COOKIE_KEY_))->encrypt($data);
    }
    /**
     * @inheritDoc
     */
    public function decrypt(string $encryptedData) : string
    {
        return (new PhpEncryption(_NEW_COOKIE_KEY_))->decrypt($encryptedData);
    }
}
