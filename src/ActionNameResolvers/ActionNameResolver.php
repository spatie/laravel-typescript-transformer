<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

interface ActionNameResolver
{
    /** @return array<string> */
    public function resolve(string $controllerClass): array;
}
