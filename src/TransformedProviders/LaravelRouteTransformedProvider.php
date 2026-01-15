<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelRouteControllerCollectionsAction;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\Data\WatchEventResult;
use Spatie\TypeScriptTransformer\Events\SummarizedWatchEvent;
use Spatie\TypeScriptTransformer\Events\WatchEvent;
use Spatie\TypeScriptTransformer\Support\Loggers\Logger;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\LoggingTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\StandaloneWritingTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\WatchingTransformedProvider;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;
use Spatie\TypeScriptTransformer\Writers\Writer;

abstract class LaravelRouteTransformedProvider implements TransformedProvider, WatchingTransformedProvider, LoggingTransformedProvider, StandaloneWritingTransformedProvider
{
    protected ?string $routeCollectionHash = null;

    protected Logger $logger;

    /**
     * @param array<RouteFilter> $filters
     */
    public function __construct(
        protected ResolveLaravelRouteControllerCollectionsAction $resolveLaravelRoutControllerCollectionsAction,
        protected ?string $defaultNamespace,
        protected bool $includeRouteClosures,
        protected array $filters,
        protected string $path,
    ) {
    }

    public function directoriesToWatch(): array
    {
        return [];
    }

    public function provide(TypeScriptTransformerConfig $config): array
    {
        $routeCollection = $this->resolveLaravelRoutControllerCollectionsAction->execute(
            defaultNamespace: $this->defaultNamespace,
            includeRouteClosures: $this->includeRouteClosures,
            filters: $this->filters,
        );

        $this->routeCollectionHash = md5(serialize($routeCollection));

        return $this->resolveTransformed($routeCollection);
    }

    public function handleWatchEvent(WatchEvent $watchEvent, TransformedCollection $transformedCollection): ?WatchEventResult
    {
        if (! $watchEvent instanceof SummarizedWatchEvent) {
            return WatchEventResult::continue();
        }

        $commandParts = [
            'php',
            'artisan',
            'typescript:dump-routes',
            $this->defaultNamespace ?? 'null',
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
        }

        $transformedCollection->add(...$transformedEntities);

        return WatchEventResult::continue();
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    public function getWriter(): Writer
    {
        return new FlatModuleWriter($this->path);
    }

    /** @return array<Transformed> */
    abstract protected function resolveTransformed(RouteCollection $routeCollection): array;
}
