<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

class DefaultActionNameResolver implements ActionNameResolver
{
    /** @return array<string> */
    public function resolve(string $controllerClass): array
    {
        return explode('\\', ltrim($controllerClass, '\\'));
    }
}
