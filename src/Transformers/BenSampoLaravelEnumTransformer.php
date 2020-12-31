<?php


namespace Spatie\LaravelTypeScriptTransformer\Transformers;

use BenSampo\Enum\Enum;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;

class BenSampoLaravelEnumTransformer implements Transformer
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
            "export enum {$name} {" . PHP_EOL . $this->resolveOptions($class) . PHP_EOL . "}"
        );
    }

    private function resolveOptions(ReflectionClass $class): string
    {
        /** @var Enum $enum */
        $enum = $class->getName();

        $options = array_map(
            fn ($key) => "  {$key} = " . json_encode($enum::getValue($key)) . ",",
            $enum::getKeys()
        );

        return implode(PHP_EOL, $options);
    }
}
