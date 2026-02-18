<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteController
{
    /** @param array<string, RouteControllerAction> $actions */
    public function __construct(
        public string $controllerClass,
        public bool $invokable,
        public array $actions,
    ) {
    }
}
