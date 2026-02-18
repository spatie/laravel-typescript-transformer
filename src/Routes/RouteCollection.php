<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteCollection
{
    /**
     * @param array<string, RouteController> $controllers
     * @param array<string, RouteClosure> $closures
     */
    public function __construct(
        public array $controllers,
        public array $closures,
    ) {
    }
}
