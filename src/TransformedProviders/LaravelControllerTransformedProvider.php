<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

use function PHPUnit\Framework\isEmpty;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\ActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\GenerateControllerSupportAction;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelRouteControllerCollectionsAction;
use Spatie\LaravelTypeScriptTransformer\References\LaravelControllerReference;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteInvokableController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteParameterCollection;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProvider;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\TypeScriptTransformer\Writers\ModuleWriter;

class LaravelControllerTransformedProvider implements TransformedProvider
{
    protected ModuleWriter $writer;

    /** @param array<RouteFilter> $filters */
    public function __construct(
        protected ResolveLaravelRouteControllerCollectionsAction $resolveAction = new ResolveLaravelRouteControllerCollectionsAction(),
        protected ActionNameResolver $actionNameResolver = new StrippedActionNameResolver(),
        protected GenerateControllerSupportAction $generateSupportAction = new GenerateControllerSupportAction(),
        protected array $filters = [],
    ) {
        $this->writer = new ModuleWriter('controllers');
    }

    public function provide(TypeScriptTransformerConfig $config): array
    {
        $routeCollection = $this->resolveAction->execute(
            actionNameResolver: $this->actionNameResolver,
            includeRouteClosures: false,
            filters: $this->filters,
        );

        $transformed = [
            $this->generateSupportAction->execute()->setWriter($this->writer),
        ];

        foreach ($routeCollection->controllers as $controller) {
            $transformed[] = $this->transformController($controller)->setWriter($this->writer);
        }

        return $transformed;
    }

    protected function transformController(
        RouteController|RouteInvokableController $controller,
    ): ?Transformed {
        $location = explode('/', $controller->controllerClass);
        $controllerName = end($location);

        $code = $controller instanceof RouteInvokableController
            ? $this->buildTransformedInvokableController($controllerName, $controller)
            : $this->buildResourceControllerCode($controllerName, $controller);

        if ($code === null) {
            return null;
        }

        return new Transformed(
            $code,
            LaravelControllerReference::controller($controller->controllerClass),
            $location,
            true,
        );
    }

    protected function buildTransformedInvokableController(
        string $controllerName,
        RouteInvokableController $controller
    ): ?TypeScriptNode {
        if (isEmpty($controller->routes)) {
            return null;
        }
    }

    protected function buildInvokableControllerCode(
        string $controllerName,
        RouteInvokableController $controller
    ): string {
        $routes = $controller->routes;

        if (empty($routes)) {
            return '';
        }

        $firstRoute = $routes[0];
        $paramsType = $this->buildParamsType($firstRoute->parameters);
        $hasParams = $paramsType !== 'undefined';

        $depth = $this->calculateImportDepth($controller->controllerClass);
        $importPath = str_repeat('../', $depth).'support';

        $code = "import { createActionWithMethods } from '{$importPath}';\n\n";

        if ($hasParams) {
            $code .= "type Params = {$paramsType};\n\n";
        }

        $code .= "/**\n * {$controller->controllerClass}\n */\n";

        $paramsGeneric = $hasParams ? '<Params>' : '<undefined>';
        $code .= "const {$controllerName} = createActionWithMethods{$paramsGeneric}([\n";

        foreach ($routes as $route) {
            $method = strtolower($route->method);
            $code .= "    { method: '{$method}', url: '{$route->url}' },\n";
        }

        $code .= "]);\n\n";

        $code .= "namespace {$controllerName} {\n";
        $code .= "    export type Request = object;\n";
        $code .= "    export type Response = object;\n";
        $code .= "}\n\n";

        $code .= "export { {$controllerName} };\n";

        return $code;
    }

    protected function buildResourceControllerCode(
        string $controllerName,
        RouteController $controller
    ): string {
        if (empty($controller->actions)) {
            return '';
        }

        $depth = $this->calculateImportDepth($controller->controllerClass);
        $importPath = str_repeat('../', $depth).'support';

        $code = "import { createActionWithMethods } from '{$importPath}';\n\n";

        $paramTypes = [];
        foreach ($controller->actions as $actionName => $actions) {
            if (empty($actions)) {
                continue;
            }

            $firstAction = $actions[0];
            $paramsType = $this->buildParamsType($firstAction->parameters);

            if ($paramsType !== 'undefined') {
                $typeName = ucfirst($actionName).'Params';
                $paramTypes[$actionName] = $typeName;
                $code .= "type {$typeName} = {$paramsType};\n";
            }
        }

        if (! empty($paramTypes)) {
            $code .= "\n";
        }

        $code .= "/**\n * {$controller->controllerClass}\n */\n";
        $code .= "const {$controllerName} = {\n";

        foreach ($controller->actions as $actionName => $actions) {
            if (empty($actions)) {
                continue;
            }

            $paramsGeneric = isset($paramTypes[$actionName])
                ? "<{$paramTypes[$actionName]}>"
                : '<undefined>';

            $code .= "    {$actionName}: createActionWithMethods{$paramsGeneric}([\n";

            foreach ($actions as $action) {
                $method = strtolower($action->method);
                $code .= "        { method: '{$method}', url: '{$action->url}' },\n";
            }

            $code .= "    ]),\n";
        }

        $code .= "} as const;\n\n";

        $code .= "namespace {$controllerName} {\n";

        foreach ($controller->actions as $actionName => $actions) {
            if (empty($actions)) {
                continue;
            }

            $code .= "    export namespace {$actionName} {\n";
            $code .= "        export type Request = object;\n";
            $code .= "        export type Response = object;\n";
            $code .= "    }\n";
        }

        $code .= "}\n\n";

        $code .= "export { {$controllerName} };\n";

        return $code;
    }

    protected function buildParamsType(RouteParameterCollection $parameters): string
    {
        if (empty($parameters->parameters)) {
            return 'undefined';
        }

        $props = [];
        foreach ($parameters->parameters as $param) {
            $optional = $param->optional ? '?' : '';
            $props[] = "    {$param->name}{$optional}: string | number";
        }

        return "{\n".implode(",\n", $props)."\n}";
    }
}
