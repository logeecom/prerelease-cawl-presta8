<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\Common;

use OnlinePayments\Core\Infrastructure\ORM\Interfaces\ConditionallyDeletes;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;

/**
 * Class MemoryRepositoryWithConditionalDelete
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\Common
 */
class MemoryRepositoryWithConditionalDelete extends MemoryRepository implements ConditionallyDeletes
{
    use MockConditionalDelete;

    const THIS_CLASS_NAME = __CLASS__;
}
