<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

interface ActionNameResolver
{
    public function resolve(string $controllerClass): string;
}
