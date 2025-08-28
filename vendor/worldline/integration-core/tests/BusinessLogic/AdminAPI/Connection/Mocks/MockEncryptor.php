<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Encryption\Encryptor;

class MockEncryptor implements Encryptor
{

    /**
     * @inheritDoc
     */
    public function encrypt(string $data): string
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $encryptedData): string
    {
        return $encryptedData;
    }
}