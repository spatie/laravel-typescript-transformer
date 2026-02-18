<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteClosure
{
    /**
     * @param array<RouteParameter> $parameters
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
