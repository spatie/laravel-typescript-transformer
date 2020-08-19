<?php

namespace Spatie\LaravelTypescriptTransformer;

use Illuminate\Support\ServiceProvider;
use Spatie\LaravelTypescriptTransformer\Commands\MapOptionsToTypescriptCommand;
use Spatie\TypescriptTransformer\TypeScriptTransformerConfig;

class TypescriptTransformerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MapOptionsToTypescriptCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/typescript-transformer.php' => config_path('typescript-transformer.php'),
        ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/typescript-transformer.php',
            'typescript-transformer'
        );

        $this->app->bind(
            TypeScriptTransformerConfig::class,
            fn () => TypeScriptTransformerConfig::create()
                ->searchingPath(config('typescript-transformer.searching_path'))
                ->collectors(config('typescript-transformer.collectors'))
                ->transformers(config('typescript-transformer.transformers'))
                ->classPropertyReplacements(config('typescript-transformer.class_property_replacements'))
                ->outputFile(config('typescript-transformer.output_file'))
        );
    }
}
