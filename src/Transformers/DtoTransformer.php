<?php

namespace Spatie\LaravelTypescriptTransformer\Transformers;

use ReflectionClass;
use ReflectionProperty;
use Spatie\DataTransferObject\FieldValidator;
use Spatie\LaravelTypescriptTransformer\Actions\ResolvePropertyTypesAction;
use Spatie\TypescriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypescriptTransformer\Transformers\DtoTransformer as BaseDtoTransformer;
use Spatie\TypescriptTransformer\Transformers\Transformer;

class DtoTransformer extends BaseDtoTransformer
{
    protected function resolveTypeDefinition(ReflectionProperty $property, MissingSymbolsCollection $missingSymbolsCollection): string
    {
        $fieldValidator = FieldValidator::fromReflection($property);

        $resolvePropertyTypesAction = new ResolvePropertyTypesAction(
            $missingSymbolsCollection
        );

        $types = $resolvePropertyTypesAction->execute(
            $fieldValidator->allowedTypes,
            $fieldValidator->allowedArrayTypes,
            $fieldValidator->isNullable
        );

        return "{$property->getName()} : " . implode(' | ', $types) . ';';    }
}
