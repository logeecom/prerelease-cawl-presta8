<?php

namespace OnlinePayments\Tests\Repositories;

use OnlinePayments\Classes\Bootstrap;
use OnlinePayments\Core\Tests\Infrastructure\ORM\AbstractGenericQueueItemRepositoryTest;

/**
 * Class PrestashopGenericQueueItemRepositoryTest
 *
 * @package OnlinePayments\Tests\Repositories
 */
class PrestashopGenericQueueItemRepositoryTest extends AbstractGenericQueueItemRepositoryTest
{
    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        $opTestTableUninstallScript = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'op_test';
        \Db::getInstance()->execute($opTestTableUninstallScript);
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        Bootstrap::bootstrap(function () {
            return 'WOP';
        });
        parent::setUp();
        $this->createTestTable();
    }

    /**
     * @inheritDoc
     */
    public function getQueueItemEntityRepositoryClass(): string
    {
        return TestQueueItemRepository::getClassName();
    }

    /**
     * @inheritDoc
     */
    public function cleanUpStorage()
    {
        \Db::getInstance()->execute('DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'op_test');
    }

    /**
     * Creates a table for testing purposes.
     */
    private function createTestTable(): void
    {
        $opTestTableInstallScript =
            'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'op_test
            (
             `id` INT NOT NULL AUTO_INCREMENT,
             `type` VARCHAR(128) NOT NULL,
             `index_1` VARCHAR(255),
             `index_2` VARCHAR(255),
             `index_3` VARCHAR(255),
             `index_4` VARCHAR(255),
             `index_5` VARCHAR(255),
             `index_6` VARCHAR(255),
             `index_7` VARCHAR(255),
             `index_8` VARCHAR(255),
             `index_9` VARCHAR(255),
             `data` LONGTEXT NOT NULL,
              PRIMARY KEY(`id`)
            )
            ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        \Db::getInstance()->execute($opTestTableInstallScript);
    }
}
