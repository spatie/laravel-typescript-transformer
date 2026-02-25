<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteController
{
    /** @param array<string, RouteControllerAction> $actions */
    public function __construct(
        public string $class,
        public string $file,
        public bool $invokable,
        public array $actions,
    ) {
    }
}
