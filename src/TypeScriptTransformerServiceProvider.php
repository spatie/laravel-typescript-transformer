<?php

namespace Spatie\LaravelTypeScriptTransformer;

use Illuminate\Support\Arr;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelTypeScriptTransformer\Commands\TypeScriptTransformCommand;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\TypeScriptTransformer\Writers\TypeDefinitionWriter;

class TypeScriptTransformerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-typescript-transformer')
            ->hasConfigFile()
            ->hasCommand(TypeScriptTransformCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(
            TypeScriptTransformerConfig::class,
            fn () => TypeScriptTransformerConfig::create()
                ->autoDiscoverTypes(...Arr::wrap(config('typescript-transformer.auto_discover_types')))
                ->collectors(config('typescript-transformer.collectors'))
                ->transformers(config('typescript-transformer.transformers'))
                ->defaultTypeReplacements(config('typescript-transformer.default_type_replacements'))
                ->writer(config('typescript-transformer.writer'))
                ->outputFile(config('typescript-transformer.output_file'))
                ->writer(config('typescript-transformer.writer', TypeDefinitionWriter::class))
                ->formatter(config('typescript-transformer.formatter'))
                ->transformToNativeEnums(config('typescript-transformer.transform_to_native_enums', false))
        );
    }
}
