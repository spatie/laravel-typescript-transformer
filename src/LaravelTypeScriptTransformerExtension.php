<?php

namespace Spatie\LaravelTypeScriptTransformer;

use Carbon\CarbonInterface;
use Spatie\LaravelTypeScriptTransformer\Transformers\LaravelAttributedClassTransformer;
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelTypesTransformedProvider;
use Spatie\TypeScriptTransformer\Support\Extensions\TypeScriptTransformerExtension;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;

class LaravelTypeScriptTransformerExtension implements TypeScriptTransformerExtension
{
    public function enrich(TypeScriptTransformerConfigFactory $factory): void
    {
        $factory
            ->replaceTransformer(
                AttributedClassTransformer::class,
                LaravelAttributedClassTransformer::class
            )
            ->provider(LaravelTypesTransformedProvider::class)
            ->replaceType(CarbonInterface::class, new TypeScriptString());
    }
}
