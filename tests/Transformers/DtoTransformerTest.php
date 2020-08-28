<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\Transformers;

use ReflectionClass;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto\Dto;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Dto\OtherDto;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;
use Spatie\LaravelTypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\OtherDtoCollection;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

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
