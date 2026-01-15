<?php

namespace Spatie\LaravelTypeScriptTransformer\Support;

use Illuminate\Console\Command;
use Spatie\TypeScriptTransformer\Support\Loggers\ArrayLogger as BaseConsoleLogger;

class LaravelConsoleLogger extends BaseConsoleLogger
{
    public function __construct(
        protected Command $command
    ) {
    }

    public function error(mixed $item, ?string $title = null): void
    {
        $this->command->error($this->formatTitle($title).$this->mixedToString($item));
    }

    public function info(mixed $item, ?string $title = null): void
    {
        $this->command->info($this->formatTitle($title).$this->mixedToString($item));
    }

    public function warning(mixed $item, ?string $title = null): void
    {
        $this->command->warn($this->formatTitle($title).$this->mixedToString($item));
    }

    public function debug(mixed $item, ?string $title = null): void
    {
        $this->command->line($this->formatTitle($title).$this->mixedToString($item));
    }

    protected function formatTitle(
        ?string $title = null
    ): string {
        return $title ? "[$title] " : '';
    }
}
