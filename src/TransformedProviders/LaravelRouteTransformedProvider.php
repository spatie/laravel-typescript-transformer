<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\DefaultActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelRouteControllerCollectionsAction;
use Spatie\LaravelTypeScriptTransformer\References\LaravelNamedRouteReference;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteClosure;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteControllerAction;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteParameter;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptAlias;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptConditional;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptFunctionDeclaration;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptGeneric;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptGenericTypeParameter;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIndexedAccess;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptLiteral;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNever;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNumber;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObject;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObjectLiteral;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptOperator;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptParameter;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptProperty;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptRaw;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptTuple;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUnion;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptVariableDeclaration;

class LaravelRouteTransformedProvider extends LaravelRouteCollectionTransformedProvider
{
    /**
     * @param array<RouteFilter> $filters
     * @param array<string>|null $routeDirectories
     */
    public function __construct(
        ResolveLaravelRouteControllerCollectionsAction $resolveLaravelRoutControllerCollectionsAction = new ResolveLaravelRouteControllerCollectionsAction(),
        array $filters = [],
        string $path = 'helpers/route.ts',
        ?array $routeDirectories = null,
        protected bool $absoluteUrlsByDefault = true,
    ) {
        parent::__construct(
            resolveLaravelRoutControllerCollectionsAction: $resolveLaravelRoutControllerCollectionsAction,
            actionNameResolver: new DefaultActionNameResolver(),
            includeRouteClosures: true,
            filters: $filters,
            path: $path,
            routeDirectories: $routeDirectories
        );
    }

    /** @return Transformed[] */
    protected function resolveTransformed(RouteCollection $routeCollection): array
    {
        $routesObject = $this->resolveActionCollection($routeCollection)
            ->mapWithKeys(fn (RouteControllerAction|RouteClosure $entity) => [
                $entity->name => $entity->url,
            ])
            ->all();

        $transformedRoutes = new Transformed(
            TypeScriptVariableDeclaration::const(
                'routes',
                new TypeScriptObjectLiteral($routesObject)
            ),
            LaravelNamedRouteReference::routes(),
            [],
            false,
        );

        $transformedRouteParameters = new Transformed(
            new TypeScriptAlias(
                new TypeScriptIdentifier('RouteParameters'),
                $this->parseRouteCollection($routeCollection),
            ),
            LaravelNamedRouteReference::routeParameters(),
            [],
            false,
        );

        $transformedRouteFunction = new Transformed(
            new TypeScriptFunctionDeclaration(
                new TypeScriptGeneric(
                    new TypeScriptIdentifier('route'),
                    [
                        new TypeScriptGenericTypeParameter(
                            new TypeScriptIdentifier('T'),
                            extends: TypeScriptOperator::keyof(new TypeScriptIdentifier('RouteParameters'))
                        ),
                    ]
                ),
                [
                    new TypeScriptParameter('name', new TypeScriptIdentifier('T')),
                    new TypeScriptParameter(
                        'parameters',
                        new TypeScriptConditional(
                            TypeScriptOperator::extends(
                                new TypeScriptTuple([
                                    new TypeScriptIndexedAccess(
                                        new TypeScriptIdentifier('RouteParameters'),
                                        [new TypeScriptIdentifier('T')]
                                    ),
                                ]),
                                new TypeScriptTuple([new TypeScriptNever()])
                            ),
                            new TypeScriptGeneric(
                                new TypeScriptIdentifier('Record'),
                                [new TypeScriptString(), new TypeScriptNever()]
                            ),
                            new TypeScriptIndexedAccess(
                                new TypeScriptIdentifier('RouteParameters'),
                                [new TypeScriptIdentifier('T')]
                            )
                        ),
                        isOptional: true
                    ),
                    new TypeScriptParameter('absolute', new TypeScriptIdentifier('boolean'), defaultValue: new TypeScriptLiteral($this->absoluteUrlsByDefault)),
                ],
                new TypeScriptString(),
                new TypeScriptRaw(
                    <<<'TS'
let url: string = '/' + routes[name];

if (parameters) {
    for (const [key, value] of Object.entries(parameters)) {
        url = url.replace(`{${key}}`, String(value));
    }
}

if (absolute) {
    url = window.location.origin + url;
}

return url;
TS
                )
            ),
            LaravelNamedRouteReference::function(),
            [],
            true,
        );

        return [
            $transformedRoutes,
            $transformedRouteParameters,
            $transformedRouteFunction,
        ];
    }

    protected function parseRouteCollection(RouteCollection $collection): TypeScriptNode
    {
        $properties = $this->resolveActionCollection($collection)
            ->map(function (RouteControllerAction|RouteClosure $entity) {
                $parameters = array_map(fn (RouteParameter $parameter) => new TypeScriptProperty(
                    $parameter->name,
                    new TypeScriptUnion([new TypeScriptString(), new TypeScriptNumber()]),
                    isOptional: $parameter->optional,
                ), $entity->parameters);

                $type = empty($parameters)
                    ? new TypeScriptNever()
                    : new TypeScriptObject($parameters);

                return new TypeScriptProperty($entity->name, $type);
            })
            ->values()
            ->all();

        return new TypeScriptObject($properties);
    }

    /**
     * @return Collection<RouteControllerAction|RouteClosure>
     */
    private function resolveActionCollection(RouteCollection $collection): Collection
    {
        return collect(array_merge($collection->controllers, $collection->closures))
            ->flatMap(function (RouteController|RouteClosure $entity) {
                if ($entity instanceof RouteClosure && $entity->name) {
                    return [$entity];
                }

                if ($entity instanceof RouteClosure) {
                    return [];
                }

                if ($entity->invokable && ($action = Arr::first($entity->actions))) {
                    return [$action];
                }

                if ($entity->invokable) {
                    return [];
                }

                return collect($entity->actions)
                    ->filter(fn (RouteControllerAction $action) => $action->name !== null)
                    ->values()
                    ->all();
            });
    }
}
