<?php

namespace Spatie\LaravelTypeScriptTransformer\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class TypeScriptTransformCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'typescript:transform
                            {--force : Force the operation to run when in production}
                            {--path= : Specify a path with classes to transform}
                            {--output= : Use another file to output}
                            {--format : Use Prettier to format the output}
                            {--check-only : Check whether the output file is up to date}';

    protected $description = 'Map PHP structures to TypeScript';

    public function handle(
        TypeScriptTransformerConfig $config
    ): int {
        $this->confirmToProceed();

        if ($inputPath = $this->resolveInputPath()) {
            $config->autoDiscoverTypes($inputPath);
        }

        if ($outputFile = $this->resolveOutputFile()) {
            $config->outputFile($outputFile);
        }

        if ($this->option('format')) {
            $config->formatter(PrettierFormatter::class);
        }

        if ($this->option('check-only')) {
            return $this->executeCheckOnly($config);
        }

        $transformer = app()->make(TypeScriptTransformer::class, [
            'config' => $config,
        ]);

        try {
            $this->ensureConfiguredCorrectly();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $collection = $transformer->transform();

        $this->table(
            ['PHP class', 'TypeScript entity'],
            collect($collection)->map(fn (TransformedType $type, string $class) => [
                $class, $type->getTypeScriptName(),
            ])
        );

        $this->info("Transformed {$collection->count()} PHP types to TypeScript");

        return 0;
    }

    private function resolveInputPath(): ?string
    {
        $path = $this->option('path');

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

    private function ensureConfiguredCorrectly()
    {
        if (config()->has('typescript-transformer.searching_path')) {
            throw new Exception('In v2 of laravel-typescript-transformer the `searching_path` key within the typescript-transformer.php config file is renamed to `auto_discover_types`');
        }
    }

    private function executeCheckOnly(TypeScriptTransformerConfig $config): int
    {
        $tempDirectory = (new TemporaryDirectory())->create();
        $tempOutputFile = $tempDirectory->path('temp.d.ts');
        $prevOutputFile = $this->resolveOutputFile() ?? $config->getOutputFile();

        $transformer = app()->make(TypeScriptTransformer::class, [
            'config' => $config->outputFile($tempOutputFile),
        ]);

        try {
            $this->ensureConfiguredCorrectly();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $transformer->transform();

        $tempOutputFileContent = ! is_null($tempOutputFile) && file_exists($tempOutputFile)
            ? file_get_contents($tempOutputFile)
            : '';

        $prevOutputFileContent = ! is_null($prevOutputFile) && file_exists($prevOutputFile)
            ? file_get_contents($prevOutputFile)
            : '';

        $tempDirectory->delete();

        if (preg_replace('/\s+/', '', trim($tempOutputFileContent)) !== preg_replace('/\s+/', '', trim($prevOutputFileContent))) {
            $this->error('Output file is not up to date');
            $this->line('Previous output file:');
            $this->line($prevOutputFileContent);
            $this->line('Current output file:');
            $this->line($tempOutputFileContent);
            return 1;
        }

        $this->info('No changes detected, output file is up to date');
        return 0;
    }
}
