<?php

namespace Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ClassPropertyProcessor;
use Spatie\TypescriptTransformer\ValueObjects\ClassProperty;

class LaravelDateClassPropertyProcessor implements ClassPropertyProcessor
{
    public function process(ClassProperty $classProperty): ClassProperty
    {
        $classProperty->types = $this->replaceProperties($classProperty->types);
        $classProperty->arrayTypes = $this->replaceProperties($classProperty->arrayTypes);

        return $classProperty;
    }

    private function replaceProperties(array $properties): array
    {
        $properties = array_map(
            fn (string $property) => $this->isDate($property) ? 'string' : $property,
            $properties
        );

        return array_unique($properties);
    }

    private function isDate(string $property)
    {
        return is_subclass_of($property, DateTimeInterface::class)
            || is_subclass_of($property, CarbonInterface::class);
    }
}
