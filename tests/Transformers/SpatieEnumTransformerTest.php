<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\Transformers;

use ReflectionClass;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Enum;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\State;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;
use Spatie\LaravelTypeScriptTransformer\Transformers\SpatieEnumTransformer;

class SpatieEnumTransformerTest extends TestCase
{
    private SpatieEnumTransformer $transformer;

    public function setUp(): void
    {
        parent::setUp();

        $this->transformer = new SpatieEnumTransformer();
    }

    /** @test */
    public function it_will_only_convert_enums()
    {
        $this->assertTrue($this->transformer->canTransform(
            new ReflectionClass(Enum::class)
        ));

        $this->assertFalse($this->transformer->canTransform(
            new ReflectionClass(State::class)
        ));
    }

    /** @test */
    public function it_can_transform_an_enum()
    {
        $type = $this->transformer->transform(
            new ReflectionClass(Enum::class),
            'FakeEnum'
        );

        $this->assertEquals("export type FakeEnum = 'draft' | 'published' | 'archived';", $type->transformed);
        $this->assertTrue($type->missingSymbols->isEmpty());
        $this->assertFalse($type->isInline);
    }
}
