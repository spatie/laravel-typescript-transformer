<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

use Illuminate\Support\Arr;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\ActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\GenerateControllerSupportAction;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelControllerMethodAction;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveRouteCollectionAction;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelController;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelControllersCollection;
use Spatie\LaravelTypeScriptTransformer\References\LaravelControllerReference;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteControllerAction;
use Spatie\TypeScriptTransformer\Collections\PhpNodeCollection;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Support\ReservedWords;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\ActionAwareTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\PhpNodesAwareTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProviderActions;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptAlias;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptArrayExpression;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptCallExpression;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNamespace;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNumber;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObject;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptOperator;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptProperty;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptRaw;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptReference;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUndefined;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUnion;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptVariableDeclaration;
use Spatie\TypeScriptTransformer\Writers\ModuleWriter;
use Spatie\TypeScriptTransformer\Writers\Writer;

class LaravelControllerTransformedProvider extends LaravelRouterTransformedProvider implements PhpNodesAwareTransformedProvider, ActionAwareTransformedProvider
{
    protected PhpNodeCollection $phpNodeCollection;

    protected TransformedProviderActions $providerActions;

    protected ResolveLaravelControllerMethodAction $resolveLaravelControllerMethodAction;

    protected GenerateControllerSupportAction $generateSupportAction;

    protected Writer $writer;

    /**
     * @param array<RouteFilter> $filters
     * @param array<string> $controllerDirectories
     * @param array<string>|null $routeDirectories
     */
    public function __construct(
        protected string $location = 'controllers',
        protected ActionNameResolver $actionNameResolver = new StrippedActionNameResolver(),
        array $filters = [],
        protected array $controllerDirectories = [],
        ResolveRouteCollectionAction $resolveRouteCollectionAction = new ResolveRouteCollectionAction(),
        ?array $routeDirectories = null,
        protected LaravelControllersCollection $controllersCollection = new LaravelControllersCollection(),
    ) {
        $this->generateSupportAction = new GenerateControllerSupportAction();

        parent::__construct(
            resolveRouteCollectionAction: $resolveRouteCollectionAction,
            includeRouteClosures: false,
            filters: $filters,
            routeDirectories: $routeDirectories,
            writer: new ModuleWriter($this->location),
        );
    }

    public function setPhpNodeCollection(PhpNodeCollection $phpNodeCollection): void
    {
        $this->phpNodeCollection = $phpNodeCollection;
    }

    public function setActions(TransformedProviderActions $actions): void
    {
        $this->providerActions = $actions;
        $this->resolveLaravelControllerMethodAction = new ResolveLaravelControllerMethodAction(
            transpilePhpStanAction: $actions->transpilePhpStanTypeToTypeScriptNodeAction,
            transpilePhpTypeAction: $actions->transpilePhpTypeNodeToTypeScriptNodeAction,
        );
    }

    public function directoriesToWatch(): array
    {
        return array_merge(parent::directoriesToWatch(), $this->controllerDirectories);
    }

    /** @return array<Transformed> */
    protected function resolveSupport(): array
    {
        return $this->generateSupportAction->execute();
    }

    /** @return array<Transformed> */
    protected function resolveTransformed(RouteCollection $routeCollection): array
    {
        $transformed = [];

        foreach ($routeCollection->controllers as $controller) {
            $laravelController = $this->resolveAndCacheControllerTypes($controller);

            if ($laravelController === null) {
                continue;
            }

            $items = $controller->invokable
                ? $this->buildInvokableController($controller, $laravelController)
                : $this->buildResourceController($controller, $laravelController);

            array_push($transformed, ...$items);
        }

        return $transformed;
    }

    protected function resolveAndCacheControllerTypes(RouteController $controller): ?LaravelController
    {
        $classNode = match (true) {
            $this->phpNodeCollection->has($controller->class) => $this->phpNodeCollection->get($controller->class),
            $this->phpNodeCollection->isInitialRun() => $this->phpNodeCollection->add(PhpClassNode::fromClassString($controller->class)),
            default => $this->phpNodeCollection->addByFile($controller->file),
        };

        if ($classNode === null) {
            return null;
        }

        $laravelController = $this->controllersCollection->get($controller->class);

        if ($laravelController && $laravelController->classNode === $classNode) {
            return $laravelController;
        }

        $laravelController = new LaravelController(
            fqcn: $controller->class,
            filePath: $controller->file,
            classNode: $classNode,
            location: $this->actionNameResolver->resolve($controller->class),
        );

        $this->controllersCollection->add($laravelController);

        return $laravelController;
    }

    /** @return array<Transformed> */
    protected function buildInvokableController(
        RouteController $controller,
        LaravelController $laravelController,
    ): array {
        if (empty($controller->actions)) {
            return [];
        }

        $controllerName = Arr::last($laravelController->location);
        $location = array_slice($laravelController->location, 0, -1);

        $types = $this->resolveControllerMethod($laravelController, '__invoke');

        $action = $controller->actions['__invoke'];

        return [
            new Transformed(
                TypeScriptVariableDeclaration::const(
                    $controllerName,
                    new TypeScriptCallExpression(
                        new TypeScriptReference(LaravelControllerReference::supportItem('createActionWithMethods')),
                        [new TypeScriptArrayExpression($this->buildMethodRoutes($action))],
                        [$this->buildParameters(Arr::last($controller->actions))],
                    ),
                ),
                LaravelControllerReference::controller($controller->class),
                $location,
            ),
            new Transformed(
                TypeScriptOperator::export(new TypeScriptNamespace($controllerName, [
                    TypeScriptOperator::export(new TypeScriptAlias('Request', $types['request'] ?? new TypeScriptObject([]))),
                    TypeScriptOperator::export(new TypeScriptAlias('Response', $types['response'] ?? new TypeScriptObject([]))),
                ])),
                LaravelControllerReference::types($controller->class),
                $location,
            ),
        ];
    }

    /** @return array<Transformed> */
    protected function buildResourceController(
        RouteController $controller,
        LaravelController $laravelController,
    ): array {
        if (empty($controller->actions)) {
            return [];
        }

        $controllerName = Arr::last($laravelController->location);
        $location = array_slice($laravelController->location, 0, -1);

        $actionProperties = [];

        foreach ($controller->actions as $actionName => $action) {
            $paramsType = $this->buildParameters($action);

            $actionProperties[] = new TypeScriptProperty(
                $actionName,
                new TypeScriptCallExpression(
                    new TypeScriptReference(LaravelControllerReference::supportItem('createActionWithMethods')),
                    [new TypeScriptArrayExpression($this->buildMethodRoutes($action))],
                    [$paramsType],
                ),
            );
        }

        $constNode = TypeScriptVariableDeclaration::const(
            $controllerName,
            TypeScriptOperator::as(
                new TypeScriptObject($actionProperties),
                new TypeScriptIdentifier('const'),
            ),
        );

        $subnamespaces = [];

        foreach ($controller->actions as $actionName => $action) {
            $types = $this->resolveControllerMethod($laravelController, $action->methodName);

            $namespaceName = ReservedWords::isReserved($actionName) ? "_{$actionName}" : $actionName;

            $subnamespaces[] = TypeScriptOperator::export(new TypeScriptNamespace($namespaceName, [
                TypeScriptOperator::export(new TypeScriptAlias('Request', $types['request'] ?? new TypeScriptObject([]))),
                TypeScriptOperator::export(new TypeScriptAlias('Response', $types['response'] ?? new TypeScriptObject([]))),
            ]));
        }

        return [
            new Transformed(
                $constNode,
                LaravelControllerReference::controller($controller->class),
                $location,
                true,
                $this->writer,
            ),
            new Transformed(
                new TypeScriptNamespace($controllerName, types: [], children: $subnamespaces),
                LaravelControllerReference::types($controller->class),
                $location,
                true,
                $this->writer,
            ),
        ];
    }

    /**
     * @return array{
     *     request: ?TypeScriptNode,
     *     response: ?TypeScriptNode
     * }
     */
    protected function resolveControllerMethod(LaravelController $controller, string $methodName): array
    {
        if (array_key_exists($methodName, $controller->methods)) {
            return $controller->methods[$methodName];
        }

        $result = $this->resolveLaravelControllerMethodAction->execute($controller->classNode, $methodName);

        return $controller->methods[$methodName] = $result;
    }

    /** @return array<TypeScriptRaw> */
    protected function buildMethodRoutes(RouteControllerAction $action): array
    {
        $methodPriorities = [
            'get' => 0,
            'post' => 1,
            'put' => 2,
            'patch' => 3,
            'delete' => 4,
            'head' => 5,
            'options' => 6,
        ];

        $methods = array_map(
            fn (string $method) => strtolower($method),
            $action->methods,
        );

        $methods = Arr::sort($methods, fn (string $method) => $methodPriorities[$method]);

        return array_map(
            fn (string $method) => new TypeScriptRaw("{ method: '{$method}', url: '{$action->url}' }"),
            $methods,
        );
    }

    protected function buildParameters(RouteControllerAction $action): TypeScriptNode
    {
        if (empty($action->parameters)) {
            return new TypeScriptUndefined();
        }

        return new TypeScriptObject(array_map(
            fn (array $param) => new TypeScriptProperty(
                $param['name'],
                new TypeScriptUnion([new TypeScriptString(), new TypeScriptNumber()]),
                isOptional: $param['optional'],
            ),
            $action->parameters,
        ));
    }
}
