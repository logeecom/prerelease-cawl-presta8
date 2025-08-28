<?php

namespace OnlinePayments\Core\Tests\Infrastructure\ORM;

use OnlinePayments\Core\Infrastructure\ORM\Exceptions\EntityClassException;
use OnlinePayments\Core\Infrastructure\ORM\IntermediateObject;
use OnlinePayments\Core\Infrastructure\ORM\Utility\EntityTranslator;
use OnlinePayments\Core\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;

/**
 * Class EntityTranslatorTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\ORM
 */
class EntityTranslatorTest extends BaseInfrastructureTestWithServices
{
    /**
     * @return void
     *
     * @throws EntityClassException
     */
    public function testTranslateWithoutInit()
    {
        $this->expectException(EntityClassException::class);

        $intermediate = new IntermediateObject();
        $translator = new EntityTranslator();
        $translator->translate([$intermediate]);
    }

    /**
     * @return void
     *
     * @throws EntityClassException
     */
    public function testInitOnNonEntity()
    {
        $this->expectException(EntityClassException::class);

        $translator = new EntityTranslator();
        $translator->init('\OnlinePayments\Core\Infrastructure\ORM\IntermediateObject');
    }
}
