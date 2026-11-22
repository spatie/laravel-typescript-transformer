<?php

namespace Spatie\LaravelTypeScriptTransformer\LaravelData\ClassPropertyProcessors;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Spatie\LaravelData\Attributes\Hidden as DataHidden;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\NameMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\PhpNodes\PhpAttributeNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpPropertyNode;
use Spatie\TypeScriptTransformer\References\ClassStringReference;
use Spatie\TypeScriptTransformer\Transformers\ClassPropertyProcessors\ClassPropertyProcessor;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNull;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptProperty;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptReference;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUnion;

class DataClassPropertyProcessor implements ClassPropertyProcessor
{
    /** @var array<class-string<NameMapper>, NameMapper> */
    protected static array $mappers = [];

    protected array $lazyTypes = [
        'Spatie\LaravelData\Lazy',
        'Spatie\LaravelData\Support\Lazy\ClosureLazy',
        'Spatie\LaravelData\Support\Lazy\ConditionalLazy',
        'Spatie\LaravelData\Support\Lazy\DefaultLazy',
        'Spatie\LaravelData\Support\Lazy\InertiaDeferred',
        'Spatie\LaravelData\Support\Lazy\InertiaLazy',
        'Spatie\LaravelData\Support\Lazy\LivewireLostLazy',
        'Spatie\LaravelData\Support\Lazy\RelationalLazy',
    ];

    public function __construct(
        protected array $customLazyTypes = [],
        protected bool $nullableAsOptional = false,
    ) {
        $this->lazyTypes = array_merge($this->lazyTypes, $this->customLazyTypes);
    }

    public function execute(
        PhpPropertyNode $phpPropertyNode,
        ?TypeNode $annotation,
        TypeScriptProperty $property
    ): ?TypeScriptProperty {
        if (! empty($phpPropertyNode->getAttributes(Hidden::class)) || ! empty($phpPropertyNode->getAttributes(DataHidden::class))) {
            return null;
        }

        $propertyName = $phpPropertyNode->getName();

        $outputMapper = $this->resolveOutputMapper($phpPropertyNode);

        if ($outputMapper !== null) {
            $property->name = new TypeScriptIdentifier(
                $this->applyOutputMapper($outputMapper, $propertyName)
            );
        }

        if (! $property->type instanceof TypeScriptUnion) {
            return $property;
        }

        foreach ($property->type->types as $i => $subType) {
            if ($subType instanceof TypeScriptReference && $this->shouldHideReference($subType)) {
                $property->isOptional = true;

                unset($property->type->types[$i]);
            }

            if ($this->nullableAsOptional && $subType instanceof TypeScriptNull) {
                $property->isOptional = true;

                unset($property->type->types[$i]);
            }
        }

        $property->type->types = array_values($property->type->types);

        if (count($property->type->types) === 1) {
            $property->type = $property->type->types[0];
        }

        return $property;
    }

    protected function resolveOutputMapper(PhpPropertyNode $phpPropertyNode): mixed
    {
        if ($mapper = $this->extractMapperFromAttributes($phpPropertyNode->getAttributes(MapOutputName::class), $phpPropertyNode->getAttributes(MapName::class))) {
            return $mapper;
        }

        $classNode = $phpPropertyNode->getDeclaringClass();

        if ($mapper = $this->extractMapperFromAttributes($classNode->getAttributes(MapOutputName::class), $classNode->getAttributes(MapName::class))) {
            return $mapper;
        }

        return config('data.name_mapping_strategy.output');
    }

    /**
     * @param array<PhpAttributeNode> $mapOutputNodes
     * @param array<PhpAttributeNode> $mapNodes
     */
    protected function extractMapperFromAttributes(array $mapOutputNodes, array $mapNodes): mixed
    {
        if (! empty($mapOutputNodes)) {
            return $mapOutputNodes[0]->getArgument('output');
        }

        if (! empty($mapNodes)) {
            return $mapNodes[0]->getArgument('output') ?? $mapNodes[0]->getArgument('input');
        }

        return null;
    }

    protected function applyOutputMapper(mixed $value, string $propertyName): string|int
    {
        if ($value instanceof NameMapper) {
            return $value->map($propertyName);
        }

        if (is_string($value) && is_subclass_of($value, NameMapper::class)) {
            return (self::$mappers[$value] ??= new $value())->map($propertyName);
        }

        return $value;
    }

    protected function shouldHideReference(
        TypeScriptReference $reference
    ): bool {
        if (! $reference->reference instanceof ClassStringReference) {
            return false;
        }

        return in_array($reference->reference->classString, $this->lazyTypes)
            || $reference->reference->classString === Optional::class;
    }
}
