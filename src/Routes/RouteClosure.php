<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteClosure
{
    /**
     * @param array<array{name: string, optional: bool}> $parameters
     * @param array<string> $methods
     */
    public function __construct(
        public array $parameters,
        public array $methods,
        public string $url,
        public ?string $name,
    ) {
    }
}
