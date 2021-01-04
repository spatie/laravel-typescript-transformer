<?php

namespace Spatie\LaravelTypeScriptTransformer\Transformers;

use Spatie\LaravelTypeScriptTransformer\TypeProcessors\LaravelCollectionTypeProcessor;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer as BaseDtoTransformer;
use Spatie\TypeScriptTransformer\TypeProcessors\DtoCollectionTypeProcessor;
use Spatie\TypeScriptTransformer\TypeProcessors\ReplaceDefaultsTypeProcessor;

class DtoTransformer extends BaseDtoTransformer
{
    protected function typeProcessors(): array
    {
        return [
            new ReplaceDefaultsTypeProcessor(
                $this->config->getDefaultTypeReplacements()
            ),
            new LaravelCollectionTypeProcessor(),
            new DtoCollectionTypeProcessor(),
        ];
    }
}
