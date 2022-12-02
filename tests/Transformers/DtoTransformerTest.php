<?php

use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto\Dto;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto\OtherDto;
use Spatie\LaravelTypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\OtherDtoCollection;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

beforeEach(function () {
    $this->transformer = new DtoTransformer(
        resolve(TypeScriptTransformerConfig::class)
    );
});

it('can transform a dto', function () {
    $type = $this->transformer->transform(
        new ReflectionClass(Dto::class),
        'FakeDto'
    );

    expect($type->transformed)->toMatchSnapshot();
    expect([
       OtherDto::class,
       OtherDtoCollection::class,
       ])->toEqual($type->missingSymbols->all());
    expect($type->isInline)->toBeFalse();
});
