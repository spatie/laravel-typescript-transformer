<?php

namespace Spatie\LaravelTypeScriptTransformer\RouteFilters;

use Illuminate\Routing\Route;

interface RouteFilter
{
    public function hide(Route $route): bool;
}
