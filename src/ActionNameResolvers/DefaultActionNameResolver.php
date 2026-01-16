<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

class DefaultActionNameResolver implements ActionNameResolver
{
    public function resolve(string $controllerClass): string
    {
        return str_replace('\\', '/', ltrim($controllerClass, '\\'));
    }
}
