<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteParameter
{
    public function __construct(
        public string $name,
        public bool $optional,
    ) {
    }
}
