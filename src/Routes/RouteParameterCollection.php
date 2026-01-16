<?php

namespace Spatie\LaravelTypeScriptTransformer\Routes;

class RouteParameterCollection implements RouterStructure
{
    /**
     * @param  array<RouteParameter>  $parameters
     */
    public function __construct(
        public array $parameters,
    ) {
    }
}
