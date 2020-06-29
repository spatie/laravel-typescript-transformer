<?php

namespace Spatie\LaravelTypescriptTransformer\Tests\Transformers;

use ReflectionClass;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\ChildState;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Dto\Dto;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Dto\DtoCollection;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Dto\OtherDto;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Enum;
use Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\State;
use Spatie\LaravelTypescriptTransformer\Tests\TestCase;
use Spatie\LaravelTypescriptTransformer\Transformers\DtoTransformer;
use Spatie\LaravelTypescriptTransformer\Transformers\StateTransformer;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\TypescriptTransformer\Tests\FakeClasses\Integration\OtherDtoCollection;

class DtoTransformerTest extends TestCase
{
    private DtoTransformer $transformer;

    use MatchesSnapshots;

    public function setUp(): void
    {
        parent::setUp();

        $this->transformer = new DtoTransformer();
    }

    /** @test */
    public function it_will_only_convert_dtos()
    {
        $this->assertTrue($this->transformer->canTransform(
            new ReflectionClass(Dto::class)
        ));

        $this->assertFalse($this->transformer->canTransform(
            new ReflectionClass(DtoCollection::class)
        ));
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
           OtherDtoCollection::class
       ], $type->missingSymbols->all());
       $this->assertFalse($type->isInline);
    }
}
