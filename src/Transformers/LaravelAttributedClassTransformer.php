<?php

namespace Spatie\LaravelTypeScriptTransformer\Transformers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\ClassPropertyProcessors\FixArrayLikeStructuresClassPropertyProcessor;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;

class LaravelAttributedClassTransformer extends AttributedClassTransformer
{
    protected function classPropertyProcessors(): array
    {
        $processors = parent::classPropertyProcessors();

        foreach ($processors as $processor) {
            if ($processor instanceof FixArrayLikeStructuresClassPropertyProcessor) {
                $processor->replaceArrayLikeClass(
                    Collection::class,
                    EloquentCollection::class,
                );
            }
        }

        return $processors;
    }
}
