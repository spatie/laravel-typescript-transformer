<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

class ClosureActionNameResolver implements ActionNameResolver
{
    protected SerializableClosure $resolver;

    /** @param Closure(string): array<string> $resolver */
    public function __construct(Closure $resolver)
    {
        $this->resolver = new SerializableClosure($resolver);
    }

    /** @return array<string> */
    public function resolve(string $controllerClass): array
    {
        return ($this->resolver)($controllerClass);
    }
}
