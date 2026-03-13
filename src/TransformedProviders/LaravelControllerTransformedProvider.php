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

        foreach ($routeCollection->controllers as $routeController) {
            $controller = $this->resolveAndCacheControllerTypes($routeController);

            if ($controller === null) {
                continue;
            }

            array_push($transformed, ...$this->buildController($controller));
        }

        return $transformed;
    }

    protected function resolveAndCacheControllerTypes(RouteController $routeController): ?LaravelController
    {
        $classNode = match (true) {
            $this->phpNodeCollection->has($routeController->class) => $this->phpNodeCollection->get($routeController->class),
            $this->phpNodeCollection->isInitialRun() => $this->phpNodeCollection->add(PhpClassNode::fromClassString($routeController->class)),
            default => $this->phpNodeCollection->addByFile($routeController->file),
        };

        if ($classNode === null) {
            return null;
        }

        $controller = $this->controllersCollection->get($routeController->class);

        if ($controller && $controller->classNode === $classNode) {
            $controller->routeController = $routeController;

            return $controller;
        }

        $controller = new LaravelController(
            routeController: $routeController,
            classNode: $classNode,
            location: $this->actionNameResolver->resolve($routeController->class),
        );

        $this->controllersCollection->add($controller);

        return $controller;
    }

    /** @return array<Transformed> */
    protected function buildController(LaravelController $controller): array
    {
        if (empty($controller->routeController->actions)) {
            return [];
        }

        $actionCallExpressions = [];
        $actionTypeAliases = [];

        foreach ($controller->routeController->actions as $action) {
            $types = $this->resolveControllerMethod($controller, $action->methodName);

            $actionCallExpressions[$action->methodName] = $this->buildActionCallExpression($action);

            $actionTypeAliases[$action->methodName] = [
                TypeScriptOperator::export(new TypeScriptAlias('Request', $types['request'] ?? new TypeScriptObject([]))),
                TypeScriptOperator::export(new TypeScriptAlias('Response', $types['response'] ?? new TypeScriptObject([]))),
            ];
        }

        if ($controller->routeController->invokable && (! array_key_exists('__invoke', $actionCallExpressions) || ! array_key_exists('__invoke', $actionTypeAliases))) {
            return [];
        }

        $constNode = $controller->routeController->invokable ? $actionCallExpressions['__invoke'] : TypeScriptOperator::as(
            new TypeScriptObject(array_map(
                fn (string $methodName) => new TypeScriptProperty($methodName, $actionCallExpressions[$methodName]),
                array_keys($actionCallExpressions),
            )),
            new TypeScriptIdentifier('const'),
        );

        $typesNode = $controller->routeController->invokable ?
            new TypeScriptNamespace(
                $controller->getTransformedName(),
                types: $actionTypeAliases['__invoke'],
            ) :

            new TypeScriptNamespace(
                $controller->getTransformedName(),
                types: [],
                children: array_map(
                    function (string $methodName) use ($actionTypeAliases) {
                        $namespaceName = ReservedWords::isReserved($methodName)
                            ? "_{$methodName}"
                            : $methodName;

                        return TypeScriptOperator::export(new TypeScriptNamespace($namespaceName, $actionTypeAliases[$methodName]));
                    },
                    array_keys($actionTypeAliases),
                ),
            );

        return [
            new Transformed(
                TypeScriptVariableDeclaration::const(
                    $controller->getTransformedName(),
                    $constNode
                ),
                LaravelControllerReference::controller($controller),
                $controller->getTransformedLocation(),
            ),
            new Transformed(
                $typesNode,
                LaravelControllerReference::types($controller),
                $controller->getTransformedLocation(),
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

    protected function buildActionCallExpression(RouteControllerAction $action): TypeScriptCallExpression
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

        $methodRoutes = new TypeScriptArrayExpression(array_map(
            fn (string $method) => new TypeScriptRaw("{ method: '{$method}', url: '{$action->url}' }"),
            $methods,
        ));

        $typeParameters = empty($action->parameters)
            ? []
            : [
                new TypeScriptObject(array_map(
                    fn (array $param) => new TypeScriptProperty(
                        $param['name'],
                        new TypeScriptUnion([new TypeScriptString(), new TypeScriptNumber()]),
                        isOptional: $param['optional'],
                    ),
                    $action->parameters,
                )),
            ];

        return new TypeScriptCallExpression(
            new TypeScriptReference(LaravelControllerReference::supportItem('createActionWithMethods')),
            [$methodRoutes],
            $typeParameters,
        );
    }
}
