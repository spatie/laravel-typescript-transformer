<?php

namespace Spatie\LaravelTypescriptTransformer\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\TypescriptTransformer\TypescriptTransformer;
use Spatie\TypescriptTransformer\TypeScriptTransformerConfig;

class MapOptionsToTypescriptCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'typescript:transform
                            {--class= : Specify a class to transform}
                            {--output= : Use another file to output}';

    protected $description = 'Map PHP structures to Typescript';

    public function handle(
        TypeScriptTransformerConfig $config
    ): void {
        $this->confirmToProceed();

        if ($inputPath = $this->resolveInputPath()) {
            $config->searchingPath($inputPath);
        }

        if ($outputFile = $this->resolveOutputFile()) {
            $config->outputFile($outputFile);
        }

        $transformer = new TypescriptTransformer($config);

        try {
            $collection = $transformer->transform();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return;
        }

        $this->info("Transformed {$collection->count()} PHP types to Typescript");

        foreach ($collection as $class => $type) {
            $this->info("{$class} -> {$type->getTypescriptName()}");
        }
    }

    private function resolveInputPath(): ?string
    {
        $path = $this->option('class');

        if ($path === null) {
            return null;
        }

        if (file_exists($path)) {
            return $path;
        }

        return app_path($path);
    }

    private function resolveOutputFile(): ?string
    {
        $path = $this->option('output');

        if ($path === null) {
            return null;
        }

        return resource_path($path);
    }
}
