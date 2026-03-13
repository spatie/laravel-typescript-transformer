<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses;

use Spatie\LaravelData\Data;

class FakeData extends Data
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}
