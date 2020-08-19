<?php

namespace Spatie\LaravelTypescriptTransformer\Transformers;

use Spatie\LaravelTypescriptTransformer\ClassPropertyProcessors\LaravelCollectionClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ApplyNeverClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\DtoCollectionClassPropertyProcessor;
use Spatie\TypescriptTransformer\ClassPropertyProcessors\ReplaceDefaultTypesClassPropertyProcessor;
use Spatie\TypescriptTransformer\Transformers\DtoTransformer as BaseDtoTransformer;

class DtoTransformer extends BaseDtoTransformer
{
    protected function getClassPropertyProcessors(): array
    {
        return [
            new ReplaceDefaultTypesClassPropertyProcessor(
                $this->config->getClassPropertyReplacements()
            ),
            new LaravelCollectionClassPropertyProcessor(),
            new DtoCollectionClassPropertyProcessor(),
            new ApplyNeverClassPropertyProcessor(),
        ];
    }
}
