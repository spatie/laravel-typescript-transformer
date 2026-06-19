<?php

namespace Spatie\LaravelTypeScriptTransformer\Actions;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use ReflectionClass;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteClosure;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteControllerAction;

class ResolveRouteCollectionAction
{
    /** @param array<RouteFilter> $filters */
    public function execute(
        bool $includeRouteClosures,
        array $filters = [],
    ): RouteCollection {
        /** @var array<string, RouteController> $controllers */
        $controllers = [];
        /** @var array<RouteClosure> $closures */
        $closures = [];

        foreach (app(Router::class)->getRoutes()->getRoutes() as $route) {
            foreach ($filters as $filter) {
                if ($filter->hide($route)) {
                    continue 2;
                }
            }

            $controllerClass = $route->getControllerClass();

            if ($controllerClass === null && ! $includeRouteClosures) {
                continue;
            }

            if ($controllerClass === null) {
                $name = "Closure({$route->uri})";

                $closures[$name] = new RouteClosure(
                    $this->resolveRouteParameters($route),
                    $route->methods,
                    $this->resolveUrl($route),
                    $route->getName(),
                );

                continue;
            }

            $controllerFile = $this->resolveControllerFile($controllerClass);

            if ($controllerFile === null) {
                continue;
            }

            $invokable = $route->getActionMethod() === $route->getControllerClass();

            if (! array_key_exists($controllerClass, $controllers)) {
                $controllers[$controllerClass] = new RouteController(
                    class: $controllerClass,
                    file: $controllerFile,
                    invokable: $invokable,
                    actions: [],
                );
            }

            $controllers[$controllerClass]->actions[] = new RouteControllerAction(
                methodName: $invokable ? '__invoke' : $route->getActionMethod(),
                parameters: $this->resolveRouteParameters($route),
                methods: $route->methods,
                url: $this->resolveUrl($route),
                name: $route->getName(),
            );
        }

        return new RouteCollection($controllers, $closures);
    }

    /**
     * @return array<array{name: string, optional: bool}>
     */
    protected function resolveRouteParameters(Route $route): array
    {
        preg_match_all('/\{(.*?)\}/', $route->getDomain().$route->uri, $matches);

        return array_map(fn (string $match) => [
            'name' => trim($match, '?'),
            'optional' => str_ends_with($match, '?'),
        ], $matches[1]);
    }

    protected function resolveControllerFile(string $controllerClass): ?string
    {
        $fileName = (new ReflectionClass($controllerClass))->getFileName();

        return $fileName ?: null;
    }

    protected function resolveUrl(Route $route): string
    {
        return str_replace('?}', '}', $route->getDomain().ltrim($route->uri, '/'));
    }
}
