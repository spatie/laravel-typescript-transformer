<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\Transformers;

use DateTime;
use ReflectionClass;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ChildState;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\State;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;
use Spatie\LaravelTypeScriptTransformer\Transformers\SpatieStateTransformer;

class SpatieStateTransformerTest extends TestCase
{
    private SpatieStateTransformer $transformer;

    public function setUp(): void
    {
        parent::setUp();

        $this->transformer = new SpatieStateTransformer();
    }

    /** @test */
    public function it_will_only_convert_states()
    {
        $this->assertNotNull($this->transformer->transform(
            new ReflectionClass(State::class),
            'State'
        ));

        $this->assertNull($this->transformer->transform(
            new ReflectionClass(ChildState::class),
            'State'
        ));

        $this->assertNull($this->transformer->transform(
            new ReflectionClass(DateTime::class),
            'State'
        ));
    }

    /** @test */
    public function it_can_transform_an_state()
    {
        $type = $this->transformer->transform(
            new ReflectionClass(State::class),
            'FakeState'
        );

        $this->assertEquals("'child' | 'other_child'", $type->transformed);
        $this->assertTrue($type->missingSymbols->isEmpty());
        $this->assertFalse($type->isInline);
    }
}
