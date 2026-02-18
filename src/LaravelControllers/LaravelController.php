<?php

namespace Spatie\LaravelTypeScriptTransformer\LaravelControllers;

use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;

class LaravelController
{
    /**
     * @param array<string, array{
     *     request: ?TypeScriptNode,
     *     response: ?TypeScriptNode
     * }> $methods
     */
    public function __construct(
        public string $fqcn,
        public string $filePath,
        public array $methods,
    ) {
    }
}
