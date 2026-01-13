<?php

namespace Spatie\LaravelTypeScriptTransformer;

use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\NamespaceWriter;

abstract class TypeScriptTransformerApplicationServiceProvider extends ServiceProvider
{
    abstract protected function configure(TypeScriptTransformerConfigFactory $config): void;

    public function register(): void
    {
        $this->app->singleton(TypeScriptTransformerConfig::class, function () {
            $builder = (new TypeScriptTransformerConfigFactory())
                ->outputDirectory(resource_path('js/generated'))
                ->configPath((new ReflectionClass($this))->getFileName())
                ->writer(new NamespaceWriter());

            $this->configure($builder);

            return $builder->get();
        });
    }
}
