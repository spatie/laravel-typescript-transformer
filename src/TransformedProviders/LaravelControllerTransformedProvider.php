<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

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
use Spatie\TypeScriptTransformer\Data\WritingContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\ActionAwareTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\PhpNodesAwareTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProviderActions;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptAlias;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptArrayExpression;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptCallExpression;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptExport;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNamespace;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNumber;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObject;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptOperator;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptProperty;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptRaw;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUndefined;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUnion;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptVariableDeclaration;
use Spatie\TypeScriptTransformer\Writers\ModuleWriter;
use Spatie\TypeScriptTransformer\Writers\Writer;

class LaravelControllerTransformedProvider extends LaravelRouteCollectionTransformedProvider implements PhpNodesAwareTransformedProvider, ActionAwareTransformedProvider
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
        return [
            $this->generateSupportAction->execute(),
        ];
    }

    /** @return array<Transformed> */
    protected function resolveTransformed(RouteCollection $routeCollection): array
    {
        $transformed = [];

        foreach ($routeCollection->controllers as $controllerClass => $routeController) {
            $controller = $this->resolveAndCacheControllerTypes($routeController);

            if ($controller === null) {
                continue;
            }

            $location = $this->actionNameResolver->resolve($controllerClass);

            $items = $routeController->invokable
                ? $this->buildInvokableController($controllerClass, $location, $routeController, $controller)
                : $this->buildResourceController($controllerClass, $location, $routeController, $controller);

            array_push($transformed, ...$items);
        }

        return $transformed;
    }

    protected function resolveAndCacheControllerTypes(RouteController $routeController): ?LaravelController
    {
        $controller = $this->controllersCollection->get($routeController->class);

        if ($controller) {
            return $controller;
        }

        $classNode = match(true){
            $this->phpNodeCollection->has($routeController->class) => $this->phpNodeCollection->get($routeController->class),
            $this->phpNodeCollection->isInitialRun() => $this->phpNodeCollection->add(PhpClassNode::fromClassString($routeController->class)),
            default => $this->phpNodeCollection->addByFile($routeController->file),
        };

        if ($classNode === null) {
            return null;
        }

        $controller = new LaravelController(
            fqcn: $routeController->class,
            filePath: $routeController->file,
            classNode: $classNode,
        );

        $this->controllersCollection->add($controller);

        return $controller;
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

    /**
     * @param array<string> $location
     *
     * @return array<Transformed>
     */
    protected function buildInvokableController(
        string $controllerClass,
        array $location,
        RouteController $routeController,
        LaravelController $controller,
    ): array {
        if (empty($routeController->actions)) {
            return [];
        }

        $controllerName = end($location);
        $action = reset($routeController->actions);
        $types = $this->resolveControllerMethod($controller, '__invoke');

        return [
            new Transformed(
                $this->buildActionCall($controllerName, $action),
                LaravelControllerReference::controller($controllerClass),
                $location,
                true,
                $this->writer,
            ),
            new Transformed(
                new TypeScriptNamespace($controllerName, [
                    new TypeScriptExport(new TypeScriptAlias('Request', $types['request'] ?? new TypeScriptObject([]))),
                    new TypeScriptExport(new TypeScriptAlias('Response', $types['response'] ?? new TypeScriptObject([]))),
                ], declare: false),
                new LaravelControllerReference($controllerClass, 'types'),
                $location,
                false,
                $this->writer,
            ),
        ];
    }

    /**
     * @param array<string> $location
     *
     * @return array<Transformed>
     */
    protected function buildResourceController(
        string $controllerClass,
        array $location,
        RouteController $routeController,
        LaravelController $controller,
    ): array {
        if (empty($routeController->actions)) {
            return [];
        }

        $controllerName = end($location);

        $actionEntries = [];

        foreach ($routeController->actions as $actionName => $action) {
            $paramsType = $this->buildParamsType($action->parameters);
            $method = strtolower($action->methods[0] ?? 'get');

            $actionEntries[] = "    {$actionName}: ".$this->writeNode(
                new TypeScriptCallExpression(
                    new TypeScriptIdentifier('createActionWithMethods'),
                    [
                        new TypeScriptArrayExpression([
                            new TypeScriptRaw("{ method: '{$method}', url: '{$action->url}' }"),
                        ]),
                    ],
                    [$paramsType ?? new TypeScriptUndefined()],
                ),
            ).',';
        }

        $objectBody = "{\n".implode("\n", $actionEntries)."\n}";

        $constNode = TypeScriptVariableDeclaration::const(
            $controllerName,
            TypeScriptOperator::as(
                new TypeScriptRaw($objectBody),
                new TypeScriptIdentifier('const'),
            ),
        );

        $namespaceTypes = [];

        foreach ($routeController->actions as $actionName => $action) {
            $types = $this->resolveControllerMethod($controller, $action->methodName);

            $namespaceTypes[] = new TypeScriptRaw(
                "export namespace {$actionName} {\n".
                "    export type Request = {$this->writeNode($types['request'] ?? new TypeScriptObject([]))};\n".
                "    export type Response = {$this->writeNode($types['response'] ?? new TypeScriptObject([]))};\n".
                '}'
            );
        }

        return [
            new Transformed(
                $constNode,
                LaravelControllerReference::controller($controllerClass),
                $location,
                true,
                $this->writer,
            ),
            new Transformed(
                new TypeScriptNamespace($controllerName, $namespaceTypes, declare: false),
                new LaravelControllerReference($controllerClass, 'types'),
                $location,
                false,
                $this->writer,
            ),
        ];
    }

    protected function buildActionCall(string $name, RouteControllerAction $action): TypeScriptNode
    {
        $paramsType = $this->buildParamsType($action->parameters);
        $method = strtolower($action->methods[0] ?? 'get');

        return TypeScriptVariableDeclaration::const(
            $name,
            new TypeScriptCallExpression(
                new TypeScriptIdentifier('createActionWithMethods'),
                [
                    new TypeScriptArrayExpression([
                        new TypeScriptRaw("{ method: '{$method}', url: '{$action->url}' }"),
                    ]),
                ],
                [$paramsType ?? new TypeScriptUndefined()],
            ),
        );
    }

    protected function writeNode(TypeScriptNode $node): string
    {
        return $node->write(new WritingContext([]));
    }

    /**
     * @param array<array{name: string, optional: bool}> $parameters
     */
    protected function buildParamsType(array $parameters): ?TypeScriptNode
    {
        if (empty($parameters)) {
            return null;
        }

        return new TypeScriptObject(array_map(
            fn (array $param) => new TypeScriptProperty(
                $param['name'],
                new TypeScriptUnion([new TypeScriptString(), new TypeScriptNumber()]),
                isOptional: $param['optional'],
            ),
            $parameters,
        ));
    }
}
