<?php

namespace Spatie\LaravelTypescriptTransformer\Transformers;

use ReflectionClass;
use Spatie\Enum\Enum;
use Spatie\TypescriptTransformer\Structures\TransformedType;
use Spatie\TypescriptTransformer\Transformers\Transformer;

class SpatieEnumTransformer implements Transformer
{
    public function canTransform(ReflectionClass $class): bool
    {
        return $class->isSubclassOf(Enum::class);
    }

    public function transform(ReflectionClass $class, string $name): TransformedType
    {
        return TransformedType::create(
            $class,
            $name,
            "export type {$name} = {$this->resolveOptions($class)};"
        );
    }

    private function resolveOptions(ReflectionClass $class): string
    {
        /** @var \Spatie\Enum\Enum $enum */
        $enum = $class->getName();

        $options = array_map(
            fn (string $enum) => "'{$enum}'",
            array_keys($enum::toArray())
        );

        return implode(' | ', $options);
    }
}
