<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteControllerAction
{
    /**
     * @param array<RouteParameter> $parameters
     * @param array<string> $methods
     */
    public function __construct(
        public string $methodName,
        public array $parameters,
        public array $methods,
        public string $url,
        public ?string $name,
    ) {
    }
}
