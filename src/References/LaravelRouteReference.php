<?php

namespace Spatie\LaravelTypeScriptTransformer\References;

use Spatie\TypeScriptTransformer\References\Reference;

abstract class LaravelRouteReference implements Reference
{
    final protected function __construct(
        protected string $key,
    ) {
    }

    public function getKey(): string
    {
        return "{$this->getKind()}::{$this->key}";
    }

    public function humanFriendlyName(): string
    {
        return "{$this->getKind()}::{$this->key}";
    }

    abstract protected function getKind(): string;

    public static function list(): static
    {
        return new static('list');
    }

    public static function function(): static
    {
        return new static('function');
    }

    public static function baseUrl(): static
    {
        return new static('baseUrl');
    }

    public static function routes(): static
    {
        return new static('routes');
    }

    public static function routeParameters(): static
    {
        return new static('routeParameters');
    }

    public static function routeArgs(): static
    {
        return new static('routeArgs');
    }
}
