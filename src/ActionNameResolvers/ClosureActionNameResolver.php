<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

class ClosureActionNameResolver implements ActionNameResolver
{
    protected SerializableClosure $resolver;

    /** @param Closure(string): string $resolver */
    public function __construct(Closure $resolver)
    {
        $this->resolver = new SerializableClosure($resolver);
    }

    public function resolve(string $controllerClass): string
    {
        return ($this->resolver)($controllerClass);
    }
}
