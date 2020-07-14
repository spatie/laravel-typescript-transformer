<?php

namespace Spatie\LaravelTypescriptTransformer\Tests\FakeClasses;

use Spatie\Enum\Enum as BaseEnum;

/**
 * @method static self draft()
 * @method static self published()
 * @method static self archived()
 * @typescript
 */
class Enum extends BaseEnum
{
}