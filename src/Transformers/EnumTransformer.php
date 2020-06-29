<?php

namespace Spatie\LaravelTypescriptTransformer\Transformers;

use ReflectionClass;
use Spatie\Enum\Enum;
use Spatie\TypescriptTransformer\Structures\Type;
use Spatie\TypescriptTransformer\Transformers\Transformer;

class EnumTransformer implements Transformer
{
    public function canTransform(ReflectionClass $class): bool
    {
        return $class->isSubclassOf(Enum::class);
    }

    public function transform(ReflectionClass $class, string $name): Type
    {
        return Type::create(
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
            fn(string $enum) => "'{$enum}'",
            $enum::toArray()
        );

        return implode(' | ', $options);
    }
}
