<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses;

use Spatie\LaravelData\Attributes\Hidden as DataHidden;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\CustomLazy;
use Spatie\TypeScriptTransformer\Attributes\Hidden;

class DataWithAttributes extends Data
{
    public function __construct(
        public string $plain,

        #[Hidden]
        #[DataHidden]
        public string $bothHidden,

        #[Hidden]
        public string $onlyTsHidden,

        #[DataHidden]
        public string $onlyDataHidden,

        #[MapOutputName('mapped_output')]
        public string $withMapOutputName,

        #[MapName(input: 'input_name', output: 'output_name')]
        public string $withMapNameBothSet,

        public string|Lazy $lazyProperty,

        public string|Optional $optionalProperty,

        public string|int|Lazy|Optional $lazyAndOptionalProperty,

        public string|CustomLazy $customLazyProperty,

        public ?string $nullableProperty,
    ) {
    }
}
