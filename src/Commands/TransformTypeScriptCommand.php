<?php

namespace Spatie\LaravelTypeScriptTransformer\Commands;

use Exception;
use Illuminate\Console\Command;
use Spatie\LaravelTypeScriptTransformer\Support\LaravelConsoleLogger;
use Spatie\TypeScriptTransformer\Enums\RunnerMode;
use Spatie\TypeScriptTransformer\Runners\Runner;
use Spatie\TypeScriptTransformer\Support\Loggers\MultiLogger;
use Spatie\TypeScriptTransformer\Support\Loggers\RayLogger;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class TransformTypeScriptCommand extends Command
{
    public $signature = 'typescript:transform {--watch : Watch for changes and re-transform} {--worker : (internal) Run the worker process)}';

    public $description = 'Transforms PHP to TypeScript';

    public function handle(): int
    {
        if (! app()->has(TypeScriptTransformerConfig::class)) {
            $this->error('Please, first publish the TypeScriptTransformerServiceProvider and configure it.');

            return self::FAILURE;
        }

        $runner = new Runner();

        return $runner->run(
            logger: new MultiLogger([
                new RayLogger(),
                new LaravelConsoleLogger($this),
            ]),
            config: app(TypeScriptTransformerConfig::class),
            mode: match ([$this->option('watch'), $this->option('worker')]) {
                [false, false] => RunnerMode::Direct,
                [true, false] => RunnerMode::Master,
                [true, true] => RunnerMode::Worker,
                default => throw new Exception('A worker only needs to be started in watch mode.'),
            },
            workerCommand: fn (bool $watch) => 'artisan typescript:transform --worker '.($watch ? '--watch ' : ''),
        );
    }
}
