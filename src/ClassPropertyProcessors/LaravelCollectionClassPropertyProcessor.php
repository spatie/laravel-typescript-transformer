<?php

namespace Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors;

use Illuminate\Support\Enumerable;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionProperty;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ClassPropertyProcessor;

class LaravelCollectionClassPropertyProcessor implements ClassPropertyProcessor
{
    public function process(Type $type, ReflectionProperty $reflection): Type
    {
        if (! $this->hasLaravelCollection($reflection)) {
            return $type;
        }

        return $this->replaceLaravelCollection($type);
    }

    private function hasLaravelCollection(ReflectionProperty $reflection): bool
    {
        $type = $reflection->getType();

        if ($type === null) {
            return false;
        }

        return $this->isLaravelCollection($type->getName());
    }

    private function replaceLaravelCollection(Type $type): Type
    {
        if ($type instanceof Array_) {
            return $type;
        }

        if ($this->isLaravelCollectionObject($type)) {
            return new Array_();
        }

        if ($type instanceof Nullable) {
            return $this->replaceLaravelCollectionInNullable($type);
        }

        if ($type instanceof Compound) {
            return $this->replaceLaravelCollectionInCompound($type);
        }

        return new Compound([$type, new Array_()]);
    }

    private function replaceLaravelCollectionInCompound(Compound $compound): Compound
    {
        $types = iterator_to_array($compound->getIterator());

        $arraysInType = array_filter(
            $types,
            function (Type $type) {
                if ($type instanceof Nullable) {
                    return $type->getActualType() instanceof Array_;
                }

                return $type instanceof Array_;
            }
        );

        $types = array_filter(
            $types,
            function (Type $type) {
                if ($type instanceof Nullable) {
                    return ! $this->isLaravelCollectionObject($type->getActualType());
                }

                return ! $this->isLaravelCollectionObject($type);
            }
        );

        return empty($arraysInType)
            ? new Compound(array_merge($types, [new Array_()]))
            : new Compound($types);
    }

    private function replaceLaravelCollectionInNullable(Nullable $nullable): Nullable
    {
        $actualType = $nullable->getActualType();

        if ($this->isLaravelCollectionObject($actualType)) {
            return new Nullable(new Array_());
        }

        if ($actualType instanceof Compound) {
            return new Nullable($this->replaceLaravelCollectionInCompound($actualType));
        }

        if ($actualType instanceof Array_) {
            return $nullable;
        }

        return new Nullable(
            new Compound([$actualType, new Array_()])
        );
    }

    private function isLaravelCollection(string $class): bool
    {
        return class_exists($class) && in_array(Enumerable::class, class_implements($class));
    }

    private function isLaravelCollectionObject(Type $type): bool
    {
        if (! $type instanceof Object_) {
            return false;
        }

        return $this->isLaravelCollection((string) $type->getFqsen());
    }

    private function nullifyCollection(Type $type, ReflectionProperty $reflectionProperty): Type
    {
    }
}
