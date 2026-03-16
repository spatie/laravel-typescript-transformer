<?php

namespace Spatie\LaravelTypeScriptTransformer\LaravelData\ClassPropertyProcessors;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Spatie\LaravelData\Attributes\Hidden as DataHidden;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\NameMapper;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
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
        protected DataConfig $dataConfig,
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
        if (! empty($phpPropertyNode->getAttributes(Hidden::class)) && ! empty($phpPropertyNode->getAttributes(DataHidden::class))) {
            return null;
        }

        $propertyName = $phpPropertyNode->getName();

        $mapOutputNodes = $phpPropertyNode->getAttributes(MapOutputName::class);
        $mapNodes = $phpPropertyNode->getAttributes(MapName::class);

        if (empty($mapOutputNodes) && empty($mapNodes)) {
            $classNode = $phpPropertyNode->getDeclaringClass();
            $mapOutputNodes = $classNode->getAttributes(MapOutputName::class);
            $mapNodes = $classNode->getAttributes(MapName::class);
        }

        if (! empty($mapOutputNodes)) {
            $property->name = new TypeScriptIdentifier(
                $this->resolveOutputName($mapOutputNodes[0]->getArgument('output'), $propertyName)
            );
        }

        if (! empty($mapNodes)) {
            $property->name = new TypeScriptIdentifier(
                $this->resolveOutputName($mapNodes[0]->getArgument('output') ?? $mapNodes[0]->getArgument('input'), $propertyName)
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

    protected function resolveOutputName(mixed $value, string $propertyName): string|int
    {
        if ($value instanceof NameMapper) {
            return $value->map($propertyName);
        }

        if (is_string($value) && class_exists($value) && is_subclass_of($value, NameMapper::class)) {
            return (new $value())->map($propertyName);
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
