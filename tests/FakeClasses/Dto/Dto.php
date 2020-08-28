<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Spatie\DataTransferObject\DataTransferObject;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\OtherDtoCollection;

class Dto extends DataTransferObject
{
    /** @var \Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto\OtherDto[] */
    public array $other_dto_array;

    public OtherDtoCollection $other_dto_collection;

    public Collection $non_typed_laravel_collection;

    /** @var \Illuminate\Support\Collection|\Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto\OtherDto[] */
    public Collection $other_dto_laravel_collection;

    /** @var \Illuminate\Database\Eloquent\Collection|\Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto\OtherDto[] */
    public EloquentCollection $other_dto_laravel_eloquent_collection;
}
