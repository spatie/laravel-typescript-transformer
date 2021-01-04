<?php

namespace Spatie\LaravelTypeScriptTransformer\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class TypeScriptTransformCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'typescript:transform
                            {--class= : Specify a class to transform}
                            {--output= : Use another file to output}
                            {--format : Format the TypeScript}';

    protected $description = 'Map PHP structures to TypeScript';

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

        if ($this->option('format')) {
            $config->enableFormatting(true);
        }

        $transformer = new TypeScriptTransformer($config);

        try {
            $collection = $transformer->transform();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return;
        }

        $this->table(
            ['PHP class', 'TypeScript entity'],
            collect($collection)->map(fn(TransformedType $type, string $class) => [
                $class, $type->getTypeScriptName(),
            ])
        );

        $this->info("Transformed {$collection->count()} PHP types to TypeScript");
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
