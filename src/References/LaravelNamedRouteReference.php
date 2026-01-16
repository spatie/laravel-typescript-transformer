<?php

namespace Spatie\LaravelTypeScriptTransformer\References;

class LaravelNamedRouteReference extends LaravelRouteReference
{
    protected function getKind(): string
    {
        return 'laravel-named-route';
    }
}
