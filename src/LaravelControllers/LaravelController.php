<?php

namespace Spatie\LaravelTypeScriptTransformer\LaravelControllers;

use Illuminate\Support\Arr;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;

class LaravelController
{
    /**
     * @param array<string> $location
     * @param array<string, array{
     *     request: ?TypeScriptNode,
     *     response: ?TypeScriptNode
     * }> $methods
     */
    public function __construct(
        public RouteController $routeController,
        public PhpClassNode $classNode,
        public array $location = [],
        public array $methods = [],
    ) {
    }

    public function getTransformedName(): string
    {
        return Arr::last($this->location);
    }

    /** @return array<string> */
    public function getTransformedLocation(): array
    {
        return array_slice($this->location, 0, -1);
    }
}
