<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\ClassPropertyProcessors;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\TypeResolver;
use Spatie\LaravelTypeScriptTransformer\ClassPropertyProcessors\LaravelCollectionClassPropertyProcessor;
use Spatie\LaravelTypeScriptTransformer\Tests\Fakes\FakeReflectionProperty;
use Spatie\LaravelTypeScriptTransformer\Tests\Fakes\FakeReflectionType;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;

class LaravelCollectionClassPropertyProcessorTest extends TestCase
{
    private LaravelCollectionClassPropertyProcessor $processor;

    private TypeResolver $typeResolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->processor = new LaravelCollectionClassPropertyProcessor();

        $this->typeResolver = new TypeResolver();
    }

    /**
     * @test
     * @dataProvider cases
     *
     * @param string $initialType
     * @param string $outputType
     */
    public function it_will_process_correctly(string $initialType, string $outputType)
    {
        $type = $this->processor->process(
            $this->typeResolver->resolve($initialType),
            FakeReflectionProperty::create()
                ->withType(FakeReflectionType::create()->withType(Collection::class))
        );

        $this->assertEquals($outputType, (string) $type);
    }

    public function cases(): array
    {
        return [
            ['int[]', 'int[]'],
            ['int', 'int|array'],
            ['?int', '?int|array'],

            ['array', 'array'],
            ['array|int', 'array|int'],
            ['?array', '?array'],

            [Collection::class, 'array'],
            [Collection::class.'|int', 'int|array'],
            ['?'.Collection::class, '?array'],
        ];
    }
}
