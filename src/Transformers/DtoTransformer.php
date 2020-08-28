<?php

namespace Spatie\LaravelTypeScriptTransformer\Transformers;

use Spatie\LaravelTypeScriptTransformer\ClassPropertyProcessors\LaravelCollectionClassPropertyProcessor;
use Spatie\TypeScriptTransformer\ClassPropertyProcessors\ApplyNeverClassPropertyProcessor;
use Spatie\TypeScriptTransformer\ClassPropertyProcessors\DtoCollectionClassPropertyProcessor;
use Spatie\TypeScriptTransformer\ClassPropertyProcessors\ReplaceDefaultTypesClassPropertyProcessor;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer as BaseDtoTransformer;

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
