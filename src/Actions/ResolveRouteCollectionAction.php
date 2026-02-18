<?php

namespace Spatie\LaravelTypeScriptTransformer\Actions;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\ActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Exceptions\DuplicateActionNameException;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteClosure;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteControllerAction;


class ResolveRouteCollectionAction
{
    /** @param array<RouteFilter> $filters */
    public function execute(
        ActionNameResolver $actionNameResolver,
        bool $includeRouteClosures,
        array $filters = [],
    ): RouteCollection {
        /** @var array<string, RouteController> $controllers */
        $controllers = [];
        /** @var array<RouteClosure> $closures */
        $closures = [];
        /** @var array<string, array<string>> $nameMapping */
        $nameMapping = [];

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

            $resolvedName = $actionNameResolver->resolve($controllerClass);

            if (! array_key_exists($resolvedName, $nameMapping)) {
                $nameMapping[$resolvedName] = [];
            }

            if (! in_array($controllerClass, $nameMapping[$resolvedName])) {
                $nameMapping[$resolvedName][] = $controllerClass;
            }

            $isInvokable = $route->getActionMethod() === $route->getControllerClass();

            if ($isInvokable) {
                $controllers[$resolvedName] = new RouteController(
                    controllerClass: $controllerClass,
                    invokable: true,
                    actions: [
                        '__invoke' => new RouteControllerAction(
                            methodName: '__invoke',
                            parameters: $this->resolveRouteParameters($route),
                            methods: $route->methods,
                            url: $this->resolveUrl($route),
                            name: $route->getName(),
                        ),
                    ],
                );

                continue;
            }

            if (! array_key_exists($resolvedName, $controllers)) {
                $controllers[$resolvedName] = new RouteController(
                    controllerClass: $controllerClass,
                    invokable: false,
                    actions: [],
                );
            }

            $controllers[$resolvedName]->actions[$route->getActionMethod()] = new RouteControllerAction(
                methodName: $route->getActionMethod(),
                parameters: $this->resolveRouteParameters($route),
                methods: $route->methods,
                url: $this->resolveUrl($route),
                name: $route->getName(),
            );
        }

        $duplicates = array_filter($nameMapping, fn ($fqcns) => count($fqcns) > 1);

        if (! empty($duplicates)) {
            throw new DuplicateActionNameException($duplicates);
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

    protected function resolveUrl(Route $route): string
    {
        return str_replace('?}', '}', $route->getDomain().$route->uri);
    }
}
