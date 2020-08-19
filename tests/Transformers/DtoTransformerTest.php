<?php

namespace Spatie\LaravelTypescriptTransformer\Tests\Transformers;

use ReflectionClass;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Dto\Dto;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Dto\DtoCollection;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Dto\OtherDto;
use Spatie\LaravelTypescriptTransformer\Tests\TestCase;
use Spatie\LaravelTypescriptTransformer\Transformers\DtoTransformer;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\TypescriptTransformer\Tests\FakeClasses\Integration\OtherDtoCollection;
use Spatie\TypescriptTransformer\TypeScriptTransformerConfig;

class DtoTransformerTest extends TestCase
{
    private DtoTransformer $transformer;

    use MatchesSnapshots;

    public function setUp(): void
    {
        parent::setUp();

        $this->transformer = new DtoTransformer(
            resolve(TypeScriptTransformerConfig::class)
        );
    }

    /** @test */
    public function it_can_transform_a_dto()
    {
        $type = $this->transformer->transform(
            new ReflectionClass(Dto::class),
            'FakeDto'
        );

        $this->assertMatchesSnapshot($type->transformed);
        $this->assertEquals([
           OtherDto::class,
           OtherDtoCollection::class,
       ], $type->missingSymbols->all());
        $this->assertFalse($type->isInline);
    }
}
