<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\TypeProcessors;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use ReflectionProperty;
use Spatie\LaravelTypeScriptTransformer\Tests\Fakes\FakeReflectionProperty;
use Spatie\LaravelTypeScriptTransformer\Tests\Fakes\FakeReflectionType;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;
use Spatie\LaravelTypeScriptTransformer\TypeProcessors\LaravelCollectionTypeProcessor;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\TypeReflectors\PropertyTypeReflector;
use Spatie\TypeScriptTransformer\TypeReflectors\TypeReflector;

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

    /** @test */
    public function it_works_with_single_types()
    {
        $class = new class{
            /** @var int[] */
            public Collection $propertyA;

            /** @var ?int[] */
            public Collection $propertyB;

            /** @var int[]|null */
            public ?Collection $propertyC;

            /** @var array */
            public Collection $propertyD;

            /** @var ?array */
            public ?Collection $propertyE;

            /** @var array|null */
            public ?Collection $propertyF;

            /** @var \Illuminate\Support\Collection */
            public Collection $propertyG;

            /** @var \Illuminate\Support\Collection|int[] */
            public Collection $propertyH;

            /** @var \Illuminate\Support\Collection|int[]|null */
            public ?Collection $propertyI;
        };

        $this->assertEquals('int[]', (string) $this->processType($class, 'propertyA'));
        $this->assertEquals('?int[]', (string) $this->processType($class, 'propertyB'));
        $this->assertEquals('int[]|null', (string) $this->processType($class, 'propertyC'));
        $this->assertEquals('array', (string) $this->processType($class, 'propertyD'));
        $this->assertEquals('?array', (string) $this->processType($class, 'propertyE'));
        $this->assertEquals('array|null', (string) $this->processType($class, 'propertyF'));
        $this->assertEquals('array', (string) $this->processType($class, 'propertyG'));
        $this->assertEquals('int[]', (string) $this->processType($class, 'propertyH'));
        $this->assertEquals('int[]|null', (string) $this->processType($class, 'propertyI'));

    }

    /** @test */
    public function it_works_with_union_types()
    {
        $class = new class{
            /** @var \Illuminate\Support\Collection|int[] */
            public Collection|array $property;
        };

        $this->assertEquals('int[]', (string) $this->processType($class, 'property'));
    }

    private function processType(object $class, string $property): Type
    {
        $reflection = new ReflectionProperty($class, $property);

        return $this->processor->process(
            TypeReflector::new($reflection)->reflectFromDocblock(),
            $reflection,
            new MissingSymbolsCollection()
        );
    }
}
