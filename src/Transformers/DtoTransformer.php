<?php

namespace Spatie\LaravelTypescriptTransformer\Transformers;

use Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors\LaravelCollectionClassPropertyProcessor;
use Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors\LaravelDateClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ApplyNeverClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\CleanupClassPropertyProcessor;
use Spatie\TypescriptTransformer\Transformers\DtoTransformer as BaseDtoTransformer;

class DtoTransformer extends BaseDtoTransformer
{
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
