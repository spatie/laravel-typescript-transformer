<?php

namespace Spatie\LaravelTypescriptTransformer\Transformers;

use ReflectionClass;
use Spatie\DataTransferObject\DataTransferObject;
use Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors\LaravelCollectionClassPropertyProcessor;
use Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors\LaravelDateClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ApplyNeverClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\CleanupClassPropertyProcessor;
use Spatie\TypescriptTransformer\Transformers\ClassTransformer;

class DtoTransformer extends ClassTransformer
{
    public function canTransform(ReflectionClass $class): bool
    {
        return is_subclass_of($class->getName(), DataTransferObject::class);
    }

    protected function getClassPropertyProcessors(): array
    {
        return [
            new CleanupClassPropertyProcessor(),
            new LaravelCollectionClassPropertyProcessor(),
            new LaravelDateClassPropertyProcessor(),
            new ApplyNeverClassPropertyProcessor(),
        ];
    }
}
