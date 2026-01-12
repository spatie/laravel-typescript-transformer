<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteController implements RouterStructure
{
    /**
     * @param  array<string, RouteControllerAction>  $actions
     */
    public function __construct(
        public array $actions,
    ) {
    }
}
