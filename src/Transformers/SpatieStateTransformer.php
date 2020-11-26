<?php

namespace Spatie\LaravelTypeScriptTransformer\Transformers;

use ReflectionClass;
use Spatie\ModelStates\State;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;

class SpatieStateTransformer implements Transformer
{
    public function canTransform(ReflectionClass $class): bool
    {
        $parent = $class->getParentClass();

        if (empty($parent)) {
            return false;
        }

        return $parent->getName() === State::class;
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
        /** @var \Spatie\ModelStates\State $state */
        $state = $class->getName();

        $states = array_filter(
            $state::all()->toArray(),
            fn(string $stateClass) => $stateClass !== $state
        );

        $options = array_map(
            fn(string $stateClass) => "'{$stateClass::getMorphClass()}'",
            $states
        );

        return implode(' | ', $options);
    }
}
