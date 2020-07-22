<?php

namespace Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors;

use Illuminate\Support\Collection;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ClassPropertyProcessor;
use Spatie\TypescriptTransformer\ValueObjects\ClassProperty;

class LaravelCollectionClassPropertyProcessor implements ClassPropertyProcessor
{
    public function process(ClassProperty $classProperty): ClassProperty
    {
        $laravelCollections = array_filter(
            $classProperty->types,
            fn(string $type) => $this->isLaravelCollection($type)
        );

        return ! empty($laravelCollections)
            ? $this->removeLaravelCollections($classProperty)
            : $classProperty;
    }

    private function removeLaravelCollections(ClassProperty $classProperty): ClassProperty
    {
        $classProperty->types = array_values(array_filter(
            $classProperty->types,
            fn(string $type) => ! $this->isLaravelCollection($type)
        ));

        if (! in_array('array', $classProperty->types) && empty($allowedArrayTypes)) {
            $classProperty->types[] = 'array';
        }

        return $classProperty;
    }

    private function isLaravelCollection(string $type): bool
    {
        return is_subclass_of($type, Collection::class)
            || ltrim($type, '\\') === ltrim(Collection::class, '\\');
    }
}
