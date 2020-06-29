<?php

namespace Spatie\LaravelTypescriptTransformer\Actions;

use Illuminate\Support\Collection;
use Spatie\TypescriptTransformer\Actions\ResolvePropertyTypesAction as BaseResolvePropertyTypesAction;

class ResolvePropertyTypesAction extends BaseResolvePropertyTypesAction
{
    public function execute(
        array $allowedTypes,
        array $allowedArrayTypes,
        bool $isNullable
    ): array {
        if ($this->hasLaravelCollections($allowedTypes)) {
            $allowedTypes = $this->removeLaravelCollections($allowedTypes, $allowedArrayTypes);
        }

        return parent::execute($allowedTypes, $allowedArrayTypes, $isNullable);
    }

    private function hasLaravelCollections(array $allowedTypes): bool
    {
        $found = array_filter(
            $allowedTypes,
            fn (string $type) => $this->isLaravelCollection($type)
        );

        return ! empty($found);
    }

    private function removeLaravelCollections(array $allowedTypes, array $allowedArrayTypes): array
    {
        $allowedTypes = array_values(array_filter(
            $allowedTypes,
            fn (string $type) => ! $this->isLaravelCollection($type)
        ));

        if (! in_array('array', $allowedTypes) && empty($allowedArrayTypes)) {
            $allowedTypes[] = 'array';
        }

        return $allowedTypes;
    }

    private function isLaravelCollection(string $type): bool
    {
        return is_subclass_of($type, Collection::class)
            || ltrim($type, '\\') === ltrim(Collection::class, '\\');
    }
}
