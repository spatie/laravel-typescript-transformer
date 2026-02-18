<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\ActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveRouteCollectionAction;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\Data\WatchEventResult;
use Spatie\TypeScriptTransformer\Events\SummarizedWatchEvent;
use Spatie\TypeScriptTransformer\Events\WatchEvent;
use Spatie\TypeScriptTransformer\Support\Loggers\Logger;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\LoggingTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\WatchingTransformedProvider;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;
use Spatie\TypeScriptTransformer\Writers\Writer;

abstract class LaravelRouteCollectionTransformedProvider implements TransformedProvider, WatchingTransformedProvider, LoggingTransformedProvider
{
    protected ?string $routeCollectionHash = null;

    protected Logger $logger;

    protected Writer $writer;

    /** @param array<RouteFilter> $filters */
    public function __construct(
        protected ResolveRouteCollectionAction $resolveRouteCollectionAction,
        protected ActionNameResolver $actionNameResolver,
        protected bool $includeRouteClosures,
        protected array $filters,
        protected string $path,
        protected ?array $routeDirectories,
    ) {
        $this->routeDirectories ??= [
            base_path('routes'),
            base_path('bootstrap'),
            app_path('Providers'),
        ];

        $this->writer = new FlatModuleWriter($this->path);
    }

    public function directoriesToWatch(): array
    {
        return $this->routeDirectories;
    }

    public function provide(): array
    {
        $routeCollection = $this->resolveRouteCollectionAction->execute(
            actionNameResolver: $this->actionNameResolver,
            includeRouteClosures: $this->includeRouteClosures,
            filters: $this->filters,
        );

        $this->routeCollectionHash = md5(serialize($routeCollection));

        $transformed = $this->resolveTransformed($routeCollection);

        foreach ($transformed as $transformedItem) {
            $transformedItem->setWriter($this->writer);
        }

        return $transformed;
    }

    public function handleWatchEvent(WatchEvent $watchEvent, TransformedCollection $transformedCollection): ?WatchEventResult
    {
        if (! $watchEvent instanceof SummarizedWatchEvent) {
            return WatchEventResult::continue();
        }

        $this->logger->info('Detected changes in route collection. Updating TypeScript definitions.');

        $commandParts = [
            'php',
            'artisan',
            'typescript:dump-routes',
            serialize($this->actionNameResolver),
            $this->filters ? serialize($this->filters) : 'null',
            $this->includeRouteClosures ? '--include-route-closures' : '',
        ];

        try {
            $serialized = Process::timeout(2)
                ->run($commandParts)
                ->throw()
                ->output();
        } catch (ProcessTimedOutException) {
            $this->logger->error('The nested command to dump the routes collection timed out.');

            return WatchEventResult::continue();
        } catch (ProcessFailedException) {
            $this->logger->error('The nested command to dump the routes collection failed.');

            return WatchEventResult::continue();
        }

        if ($this->routeCollectionHash && $this->routeCollectionHash === md5($serialized)) {
            return WatchEventResult::continue();
        }

        try {
            $routesCollection = unserialize($serialized);
        } catch (\Throwable $e) {
            $this->logger->error('Could not unserialize the routes collection from the nested command.');

            return WatchEventResult::continue();
        }

        $transformedEntities = $this->resolveTransformed($routesCollection);

        foreach ($transformedEntities as $transformed) {
            $transformedCollection->remove($transformed->reference);

            $transformed->setWriter($this->writer);

            $transformedCollection->add($transformed);
        }

        return WatchEventResult::continue();
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /** @return array<Transformed> */
    abstract protected function resolveTransformed(RouteCollection $routeCollection): array;
}
