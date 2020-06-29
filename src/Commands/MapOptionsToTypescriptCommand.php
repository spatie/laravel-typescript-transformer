<?php

namespace Spatie\LaravelTypescriptTransformer\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\TypescriptTransformer\TypescriptTransformer;

class MapOptionsToTypescriptCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'typescript:transform';

    protected $description = 'Map PHP structures to Typescript';

    public function handle(
        TypescriptTransformer $transformer
    ): void {
        $this->confirmToProceed();

        try {
            $collection = $transformer->transform();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return;
        }

        $this->info("Transformed {$collection->count()} PHP types to Typescript");

        foreach ($collection->getTypes() as $class => $type) {
            $this->info("{$class} -> {$type->getTypescriptName()}");
        }
    }
}
