<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\Transformers;

use BenSampo\Enum\Enum;
use ReflectionClass;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;
use Spatie\LaravelTypeScriptTransformer\Transformers\BenSampoLaravelEnumTransformer;
use Spatie\Snapshots\MatchesSnapshots;

class BenSampoLaravelEnumTransformerTest extends TestCase
{
    use MatchesSnapshots;

    private BenSampoLaravelEnumTransformer $transformer;

    public function setUp(): void
    {
        parent::setUp();

        $this->transformer = new BenSampoLaravelEnumTransformer();
    }

    /** @test */
    public function it_will_check_if_an_enum_can_be_transformed()
    {
        $enum = new class(10) extends Enum {
            public const ADMIN = 10;
            public const USER = 20;
        };

        $noEnum = new class {
        };

        $this->assertTrue($this->transformer->canTransform(new ReflectionClass($enum)));
        $this->assertFalse($this->transformer->canTransform(new ReflectionClass($noEnum)));
    }

    /** @test */
    public function it_can_transform_an_enum()
    {
        $enum = new class('foobar') extends Enum {
            public const ADMIN = 10;
            public const USER = 20;
            public const STRING_USER = 'foobar';
        };

        $type = $this->transformer->transform(new ReflectionClass($enum), 'Enum');

        $this->assertMatchesSnapshot($type->transformed);
        $this->assertTrue($type->missingSymbols->isEmpty());
    }
}
