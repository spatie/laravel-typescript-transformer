<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\TypeProcessors;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\TypeResolver;
use Spatie\LaravelTypeScriptTransformer\Tests\Fakes\FakeReflectionProperty;
use Spatie\LaravelTypeScriptTransformer\Tests\Fakes\FakeReflectionType;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;
use Spatie\LaravelTypeScriptTransformer\TypeProcessors\LaravelCollectionTypeProcessor;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class LaravelCollectionTypeProcessorTest extends TestCase
{
    private LaravelCollectionTypeProcessor $processor;

    private TypeResolver $typeResolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->processor = new LaravelCollectionTypeProcessor();

        $this->typeResolver = new TypeResolver();
    }

    /**
     * @test
     * @dataProvider cases
     *
     * @param string $initialType
     * @param string $outputType
     */
    public function it_will_process_a_reflection_property_correctly(string $initialType, string $outputType)
    {
        $type = $this->processor->process(
            $this->typeResolver->resolve($initialType),
            FakeReflectionProperty::create()
                ->withType(FakeReflectionType::create()->withType(Collection::class)),
            new MissingSymbolsCollection()
        );

        $this->assertEquals($outputType, (string) $type);
    }

    public function cases(): array
    {
        return [
            ['int[]', 'int[]'],
            ['?int[]', '?int[]'],
            ['int[]|null', 'int[]|null'],
            ['array', 'array'],
            ['?array', '?array'],
            ['array|null', 'array|null'],
            [Collection::class, 'array'],
            [Collection::class.'|int[]', 'int[]'],
            [Collection::class.'|int[]|null', 'int[]|null'],
        ];
    }
}
