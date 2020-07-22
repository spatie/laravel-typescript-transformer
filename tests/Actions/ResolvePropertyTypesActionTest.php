<?php

namespace Spatie\LaravelTypescriptTransformer\Tests\Actions;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use ReflectionProperty;
use Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors\LaravelCollectionClassPropertyProcessor;
use Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors\LaravelDateClassPropertyProcessor;
use Spatie\LaravelTypescriptTransformer\Tests\TestCase;
use Spatie\TypescriptTransformer\Actions\ResolvePropertyTypesAction;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ApplyNeverClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\CleanupClassPropertyProcessor;
use Spatie\TypescriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypescriptTransformer\Tests\FakeClasses\Integration\Enum;
use Spatie\TypescriptTransformer\ValueObjects\ClassProperty;

class ResolvePropertyTypesActionTest extends TestCase
{
    private MissingSymbolsCollection $missingSymbols;

    private ResolvePropertyTypesAction $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->missingSymbols = new MissingSymbolsCollection();

        $this->action = new ResolvePropertyTypesAction($this->missingSymbols, [
            new CleanupClassPropertyProcessor(),
            new LaravelCollectionClassPropertyProcessor(),
            new LaravelDateClassPropertyProcessor(),
            new ApplyNeverClassPropertyProcessor(),
        ]);
    }

    /**
     * @test
     * @dataProvider typesDataProvider
     */
    public function it_can_resolve_types(
        array $allowed,
        array $arrayAllowed,
        bool $nullable,
        array $expected
    ) {
        $classProperty = ClassProperty::create(
            new class extends ReflectionProperty {
                public function __construct()
                {
                }
            },
            $nullable ? array_merge($allowed, ['null']) : $allowed,
            $arrayAllowed
        );

        $types = $this->action->execute($classProperty);

        $this->assertEquals($expected, $types);
    }

    public function typesDataProvider(): array
    {
        return [
            // Simple
            [['string'], [], false, ['string']],
            [['integer'], [], false, ['number']],
            [['boolean'], [], false, ['boolean']],
            [['double'], [], false, ['number']],
            [['null'], [], false, ['never']],
            [['object'], [], false, ['object']],
            [['array'], [], false, ['Array<never>']],

            // Objects
            [[Enum::class], [], false, ['{%' . Enum::class . '%}']],
            [[], [Enum::class], false, ['Array<{%' . Enum::class . '%}>']],

            // Arrays
            [[], ['string'], false, ['Array<string>']],
            [['string[]'], ['string'], false, ['Array<string>']],
            [['array'], ['string'], false, ['Array<string>']],
            [[], ['string', 'integer'], false, ['Array<string | number>']],

            // Mixed
            [['string', 'integer', Enum::class], [], false, ['string', 'number', '{%' . Enum::class . '%}']],
            [['string', 'integer', Enum::class], [], true, ['string', 'number', '{%' . Enum::class . '%}', 'null']],
            [[], ['string', 'integer', Enum::class], false, ['Array<string | number | {%' . Enum::class . '%}>']],

            // Nullable
            [['string', 'null'], [], false, ['string', 'null']],
            [['string', 'null'], [], true, ['string', 'null']],
            [['string'], [], true, ['string', 'null']],
            [[], ['string'], true, ['null', 'Array<string>']],
            [[], ['string', 'null'], false, ['Array<string | null>']],

            // Empty
            [[], [], false, ['never']],
            [[], [], true, ['never']],
        ];
    }

    /**
     * @test
     * @dataProvider laravelTypesDataProvider
     */
    public function it_can_resolve_laravel_types(
        array $allowed,
        array $arrayAllowed,
        bool $nullable,
        array $expected
    ) {
        $classProperty = ClassProperty::create(
            new class extends ReflectionProperty {
                public function __construct()
                {
                }
            },
            $nullable ? array_merge($allowed, ['null']) : $allowed,
            $arrayAllowed
        );

        $types = $this->action->execute($classProperty);

        $this->assertEquals($expected, $types);
    }

    public function laravelTypesDataProvider(): array
    {
        return [
            // Laravel Collections
            [[Collection::class], [], false, ['Array<never>']],
            [[EloquentCollection::class], [], false, ['Array<never>']],

            [[Collection::class], ['string'], false, ['Array<string>']],
            [[EloquentCollection::class], ['string'], false, ['Array<string>']],

            // Mixed Laravel collections
            [[Collection::class, 'array'], [], false, ['Array<never>']],
            [[Collection::class, 'array'], ['string'], false, ['Array<string>']],

            // Nullable Laravel collections
            [[Collection::class], [], true, ['null', 'Array<never>']],
            [[Collection::class], ['null', 'string'], true, ['null', 'Array<null | string>']],

            // Dates
            [[Carbon::class], [], false, ['string']],
            [[CarbonImmutable::class], [], false, ['string']],
            [[DateTime::class], [], false, ['string']],
            [[DateTimeImmutable::class], [], false, ['string']],

            // Funky dates
            [[Carbon::class, 'string'], [], false, ['string']],
            [[], [Carbon::class], false, ['Array<string>']],
            [[Collection::class], [Carbon::class], false, ['Array<string>']],
        ];
    }
}
