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
    protected static function labels(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    }
}
