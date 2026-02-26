<?php

namespace Spatie\LaravelTypeScriptTransformer\ActionNameResolvers;

class StrippedActionNameResolver implements ActionNameResolver
{
    /** @param array<string, string|null> $prefixes */
    public function __construct(
        protected array $prefixes = [],
    ) {
    }

    /** @return array<string> */
    public function resolve(string $controllerClass): array
    {
        $controllerClass = ltrim($controllerClass, '\\');

        foreach ($this->prefixes as $prefix => $replacement) {
            $prefix = ltrim($prefix, '\\');

            if (! str_starts_with($controllerClass, $prefix)) {
                continue;
            }

            $remainingSegments = explode('\\', ltrim(substr($controllerClass, strlen($prefix)), '\\'));

            if ($replacement === null) {
                return $remainingSegments;
            }

            return [$replacement, ...$remainingSegments];
        }

        return explode('\\', $controllerClass);
    }
}
