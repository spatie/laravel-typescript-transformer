<?php

namespace Spatie\LaravelTypeScriptTransformer\References;

use Spatie\TypeScriptTransformer\References\Reference;

final class LaravelRouteReference implements Reference
{
    private function __construct(
        protected string $key,
    ) {
    }

    public function getKey(): string
    {
        return "laravel-route::{$this->key}";
    }

    public function humanFriendlyName(): string
    {
        return "laravel-route::{$this->key}";
    }

    public static function routes(): self
    {
        return new self('routes');
    }

    public static function routeParameters(): self
    {
        return new self('routeParameters');
    }

    public static function function(): self
    {
        return new self('function');
    }
}
