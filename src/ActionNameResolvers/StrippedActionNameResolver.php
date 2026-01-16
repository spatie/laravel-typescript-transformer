<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

class StrippedActionNameResolver implements ActionNameResolver
{
    /** @param array<string, string|null> $prefixes */
    public function __construct(
        protected array $prefixes = [],
    ) {
    }

    public function resolve(string $controllerClass): string
    {
        $controllerClass = ltrim($controllerClass, '\\');

        foreach ($this->prefixes as $prefix => $replacement) {
            $prefix = ltrim($prefix, '\\');

            if (! str_starts_with($controllerClass, $prefix)) {
                continue;
            }

            $remaining = str_replace('\\', '/', ltrim(substr($controllerClass, strlen($prefix)), '\\'));

            if ($replacement === null) {
                return $remaining;
            }

            return $replacement.'/'.$remaining;
        }

        return '/'.str_replace('\\', '/', $controllerClass);
    }
}
