<?php

namespace Spatie\LaravelTypeScriptTransformer\References;

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

    public static function support(): static
    {
        return new static('support');
    }

    public static function controller(string $controllerClass): static
    {
        return new static($controllerClass);
    }
}
