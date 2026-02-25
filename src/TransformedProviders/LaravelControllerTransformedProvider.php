<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\ActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\GenerateControllerSupportAction;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelControllerAction;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveRouteCollectionAction;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelController;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelControllersCollection;
use Spatie\LaravelTypeScriptTransformer\References\LaravelControllerReference;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\TypeScriptTransformer\Collections\PhpNodeCollection;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\ActionAwareTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\PhpNodesAwareTransformedProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProviderActions;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptRaw;

class LaravelControllerTransformedProvider extends LaravelRouteCollectionTransformedProvider implements PhpNodesAwareTransformedProvider, ActionAwareTransformedProvider
{
    protected PhpNodeCollection $phpNodeCollection;

    protected TransformedProviderActions $providerActions;

    protected ResolveLaravelControllerAction $resolveTypesAction;

    protected GenerateControllerSupportAction $generateSupportAction;

    /**
     * @param array<RouteFilter> $filters
     * @param array<string> $controllerDirectories
     * @param array<string>|null $routeDirectories
     */
    public function __construct(
        ActionNameResolver $actionNameResolver = new StrippedActionNameResolver(),
        array $filters = [],
        protected array $controllerDirectories = [],
        ResolveRouteCollectionAction $resolveRouteCollectionAction = new ResolveRouteCollectionAction(),
        ?array $routeDirectories = null,
        protected LaravelControllersCollection $controllersCollection = new LaravelControllersCollection(),
    ) {
        $this->generateSupportAction = new GenerateControllerSupportAction();

        parent::__construct(
            resolveRouteCollectionAction: $resolveRouteCollectionAction,
            actionNameResolver: $actionNameResolver,
            includeRouteClosures: false,
            filters: $filters,
            path: 'controllers',
            routeDirectories: $routeDirectories,
        );
    }

    public function setPhpNodeCollection(PhpNodeCollection $phpNodeCollection): void
    {
        $this->phpNodeCollection = $phpNodeCollection;
    }

    public function setActions(TransformedProviderActions $actions): void
    {
        $this->providerActions = $actions;
        $this->resolveTypesAction = new ResolveLaravelControllerAction(
            transpilePhpStanAction: $actions->transpilePhpStanTypeToTypeScriptNodeAction,
            transpilePhpTypeAction: $actions->transpilePhpTypeNodeToTypeScriptNodeAction,
        );
    }

    public function directoriesToWatch(): array
    {
        return array_merge(
            parent::directoriesToWatch(),
            $this->controllerDirectories,
        );
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

        foreach ($routeCollection->controllers as $resolvedName => $routeController) {
            $controller = $this->resolveAndCacheControllerTypes($routeController);

            if($controller === null){
                continue;
            }

            $result = $this->transformController($resolvedName, $routeController);

            if ($result !== null) {
                $transformed[] = $result;
            }
        }

        return $transformed;
    }

    protected function resolveAndCacheControllerTypes(RouteController $routeController): ?LaravelController
    {
        // Todo: this will use roave reflection on intial run but it is technically valid to use reflection
        $classNode = $this->phpNodeCollection->has($routeController->class)
            ? $this->phpNodeCollection->get($routeController->class)
            : $this->phpNodeCollection->addByFile($routeController->file);

        if ($classNode === null) {
            return null;
        }

        $controller = $this->controllersCollection->get($routeController->class);

        if ($controller !== null && $controller->classNode === $classNode) {
            return $controller;
        }

        $controller = $this->resolveTypesAction->execute($classNode);

        $this->controllersCollection->add($controller);

        return $controller;
    }

    protected function transformController(string $resolvedName, RouteController $controller): ?Transformed
    {
        $location = explode('/', $resolvedName);
        $controllerName = end($location);

        $code = $controller->invokable
            ? $this->buildInvokableControllerCode($controllerName, $controller)
            : $this->buildResourceControllerCode($controllerName, $controller);

        if ($code === null) {
            return null;
        }

        return new Transformed(
            $code,
            LaravelControllerReference::controller($controller->class),
            $location,
            true,
        );
    }

    /**
     * @return array{
     *     response: ?TypeScriptNode,
     *     request: ?TypeScriptNode
     * }
     */
    protected function resolveActionTypes(string $methodName, string $controllerClass): array
    {
        $laravelController = $this->controllersCollection->get($controllerClass);

        if ($laravelController === null) {
            return ['response' => null, 'request' => null];
        }

        return $laravelController->methods[$methodName] ?? ['response' => null, 'request' => null];
    }

    protected function buildInvokableControllerCode(
        string $controllerName,
        RouteController $controller,
    ): ?TypeScriptNode {
        $action = array_values($controller->actions)[0] ?? null;

        if ($action === null) {
            return null;
        }

        $types = $this->resolveActionTypes('__invoke', $controller->class);
        $paramsType = $this->buildParamsType($action->parameters);
        $hasParams = $paramsType !== 'undefined';

        $code = '';

        if ($hasParams) {
            $code .= "type Params = {$paramsType};\n\n";
        }

        $code .= "/**\n * {$controller->class}\n */\n";

        $paramsGeneric = $hasParams ? '<Params>' : '<undefined>';
        $method = strtolower($action->methods[0] ?? 'get');
        $code .= "const {$controllerName} = createActionWithMethods{$paramsGeneric}([\n";
        $code .= "    { method: '{$method}', url: '{$action->url}' },\n";
        $code .= "]);\n\n";

        $code .= "namespace {$controllerName} {\n";
        $code .= $this->buildTypeExport('Request', $types['request']);
        $code .= $this->buildTypeExport('Response', $types['response']);
        $code .= "}\n\n";

        $code .= "export { {$controllerName} };\n";

        return new TypeScriptRaw($code);
    }

    protected function buildResourceControllerCode(
        string $controllerName,
        RouteController $controller,
    ): ?TypeScriptNode {
        if (empty($controller->actions)) {
            return null;
        }

        $code = '';

        $paramTypes = [];

        foreach ($controller->actions as $actionName => $action) {
            $paramsType = $this->buildParamsType($action->parameters);

            if ($paramsType !== 'undefined') {
                $typeName = ucfirst($actionName).'Params';
                $paramTypes[$actionName] = $typeName;
                $code .= "type {$typeName} = {$paramsType};\n";
            }
        }

        if (! empty($paramTypes)) {
            $code .= "\n";
        }

        $code .= "/**\n * {$controller->class}\n */\n";
        $code .= "const {$controllerName} = {\n";

        foreach ($controller->actions as $actionName => $action) {
            $paramsGeneric = isset($paramTypes[$actionName])
                ? "<{$paramTypes[$actionName]}>"
                : '<undefined>';

            $method = strtolower($action->methods[0] ?? 'get');
            $code .= "    {$actionName}: createActionWithMethods{$paramsGeneric}([\n";
            $code .= "        { method: '{$method}', url: '{$action->url}' },\n";
            $code .= "    ]),\n";
        }

        $code .= "} as const;\n\n";

        $code .= "namespace {$controllerName} {\n";

        foreach ($controller->actions as $actionName => $action) {
            $types = $this->resolveActionTypes($action->methodName, $controller->class);

            $code .= "    export namespace {$actionName} {\n";
            $code .= $this->buildTypeExport('Request', $types['request'], '    ');
            $code .= $this->buildTypeExport('Response', $types['response'], '    ');
            $code .= "    }\n";
        }

        $code .= "}\n\n";

        $code .= "export { {$controllerName} };\n";

        return new TypeScriptRaw($code);
    }

    protected function buildTypeExport(string $name, ?TypeScriptNode $type, string $indent = ''): string
    {
        $typeString = $type !== null ? $type->__toString() : 'object';

        return "{$indent}    export type {$name} = {$typeString};\n";
    }

    /**
     * @param array<array{name: string, optional: bool}> $parameters
     */
    protected function buildParamsType(array $parameters): string
    {
        if (empty($parameters)) {
            return 'undefined';
        }

        $props = [];

        foreach ($parameters as $param) {
            $optional = $param['optional'] ? '?' : '';
            $props[] = "    {$param['name']}{$optional}: string | number";
        }

        return "{\n".implode(",\n", $props)."\n}";
    }
}
