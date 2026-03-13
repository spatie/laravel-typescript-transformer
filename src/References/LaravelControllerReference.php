<?php

namespace Spatie\LaravelTypeScriptTransformer\References;

use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelController;
use Spatie\TypeScriptTransformer\References\Reference;

final class LaravelControllerReference implements Reference
{
    public function __construct(
        protected string $controllerClass,
        protected ?string $export = null,
    ) {
    }

    public function getKey(): string
    {
        $key = "laravel-controller::{$this->controllerClass}";

        if ($this->export) {
            $key .= "::{$this->export}";
        }

        return $key;
    }

    public function humanFriendlyName(): string
    {
        return $this->export
            ? "{$this->controllerClass}@{$this->export}"
            : $this->controllerClass;
    }

    public static function supportItem(string $name): static
    {
        return new static('support', $name);
    }

    public static function controller(LaravelController $controller): static
    {
        return new static($controller->routeController->class);
    }

    public static function types(LaravelController $controller): static
    {
        return new static($controller->routeController->class, 'types');
    }
}
