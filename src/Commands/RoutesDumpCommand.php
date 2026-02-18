<?php

namespace Spatie\LaravelTypeScriptTransformer\Commands;

use Illuminate\Console\Command;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveRouteCollectionAction;

class RoutesDumpCommand extends Command
{
    public $signature = 'typescript:dump-routes {actionNameResolver} {filters} {--include-route-closures}';

    public $description = 'Transforms Laravel route definitions to TypeScript Transformer usable format.';

    protected $hidden = true;

    public function handle(
        ResolveRouteCollectionAction $resolveRouteCollectionAction
    ): int {
        $actionNameResolver = unserialize($this->argument('actionNameResolver'));

        $filters = $this->argument('filters');

        if ($filters === 'null') {
            $filters = null;
        }

        $routeCollection = $resolveRouteCollectionAction->execute(
            actionNameResolver: $actionNameResolver,
            includeRouteClosures: $this->option('include-route-closures'),
            filters: $filters === null ? [] : unserialize($filters)
        );

        $this->output->write(serialize($routeCollection));

        return self::SUCCESS;
    }
}
