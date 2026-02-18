<?php

namespace Spatie\LaravelTypeScriptTransformer\TransformedProviders;

use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\ActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\GenerateControllerSupportAction;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelControllerAction;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelRouteControllerCollectionsAction;
use Spatie\LaravelTypeScriptTransformer\References\LaravelControllerReference;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteControllerAction;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteParameter;
use Spatie\TypeScriptTransformer\Collections\PhpNodeCollection;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\Data\WatchEventResult;
use Spatie\TypeScriptTransformer\Events\SummarizedWatchEvent;
use Spatie\TypeScriptTransformer\Events\WatchEvent;
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
        ResolveLaravelRouteControllerCollectionsAction $resolveLaravelRoutControllerCollectionsAction = new ResolveLaravelRouteControllerCollectionsAction(),
        ?array $routeDirectories = null,
    ) {
        $this->generateSupportAction = new GenerateControllerSupportAction();

        parent::__construct(
            resolveLaravelRoutControllerCollectionsAction: $resolveLaravelRoutControllerCollectionsAction,
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

    public function handleWatchEvent(WatchEvent $watchEvent, TransformedCollection $transformedCollection): ?WatchEventResult
    {
        if (! $watchEvent instanceof SummarizedWatchEvent) {
            return WatchEventResult::continue();
        }

        parent::handleWatchEvent($watchEvent, $transformedCollection);

        $changedFiles = array_merge(
            $watchEvent->createdFiles,
            $watchEvent->updatedFiles,
            $watchEvent->deletedFiles,
        );

        $controllerFilesChanged = $this->anyFilesInDirectories($changedFiles, $this->controllerDirectories);

        if ($controllerFilesChanged) {
            $this->handleControllerChanges($changedFiles, $transformedCollection);
        }

        return WatchEventResult::continue();
    }

    /** @return array<Transformed> */
    protected function resolveTransformed(RouteCollection $routeCollection): array
    {
        $transformed = [
            $this->generateSupportAction->execute(),
        ];

        foreach ($routeCollection->controllers as $resolvedName => $controller) {
            $result = $this->transformController($resolvedName, $controller);

            if ($result !== null) {
                $transformed[] = $result;
            }
        }

        return $transformed;
    }

    protected function transformController(string $resolvedName, RouteController $controller): ?Transformed
    {
        $location = explode('/', $resolvedName);
        $controllerName = end($location);

        $classNode = $this->resolveClassNode($controller->controllerClass);

        $code = $controller->invokable
            ? $this->buildInvokableControllerCode($controllerName, $controller, $classNode)
            : $this->buildResourceControllerCode($controllerName, $controller, $classNode);

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

    protected function resolveClassNode(string $controllerClass): ?PhpClassNode
    {
        if (! isset($this->phpNodeCollection)) {
            return null;
        }

        $classNode = $this->phpNodeCollection->get($controllerClass);

        if ($classNode !== null) {
            return $classNode;
        }

        if (! class_exists($controllerClass)) {
            return null;
        }

        $classNode = PhpClassNode::fromClassString($controllerClass);
        $this->phpNodeCollection->add($classNode);

        return $classNode;
    }

    /**
     * @return array{
     *     response: ?TypeScriptNode,
     *     request: ?TypeScriptNode
     * }
     */
    protected function resolveActionTypes(string $methodName, ?PhpClassNode $classNode): array
    {
        if ($classNode === null || ! isset($this->resolveTypesAction)) {
            return ['response' => null, 'request' => null];
        }

        $controller = $this->resolveTypesAction->execute($classNode);

        return $controller->methods[$methodName] ?? ['response' => null, 'request' => null];
    }

    protected function buildInvokableControllerCode(
        string $controllerName,
        RouteController $controller,
        ?PhpClassNode $classNode,
    ): ?TypeScriptNode {
        $action = array_values($controller->actions)[0] ?? null;

        if ($action === null) {
            return null;
        }

        $types = $this->resolveActionTypes('__invoke', $classNode);
        $paramsType = $this->buildParamsType($action->parameters);
        $hasParams = $paramsType !== 'undefined';

        $code = '';

        if ($hasParams) {
            $code .= "type Params = {$paramsType};\n\n";
        }

        $code .= "/**\n * {$controller->controllerClass}\n */\n";

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
        ?PhpClassNode $classNode,
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

        $code .= "/**\n * {$controller->controllerClass}\n */\n";
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
            $types = $this->resolveActionTypes($action->methodName, $classNode);

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
     * @param array<RouteParameter> $parameters
     */
    protected function buildParamsType(array $parameters): string
    {
        if (empty($parameters)) {
            return 'undefined';
        }

        $props = [];

        foreach ($parameters as $param) {
            $optional = $param->optional ? '?' : '';
            $props[] = "    {$param->name}{$optional}: string | number";
        }

        return "{\n".implode(",\n", $props)."\n}";
    }

    /**
     * @param array<string> $files
     * @param array<string> $directories
     */
    protected function anyFilesInDirectories(array $files, array $directories): bool
    {
        foreach ($files as $file) {
            foreach ($directories as $directory) {
                if (str_starts_with($file, $directory)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string> $changedFiles
     */
    protected function handleControllerChanges(array $changedFiles, TransformedCollection $transformedCollection): void
    {
        // PhpNodeCollection is already updated by the file watcher's event handlers.
        // Re-resolve the full route collection and rebuild transformed entries.
        // This is a simple approach - a future optimization could target only
        // the controllers whose files changed.
        $routeCollection = $this->resolveLaravelRoutControllerCollectionsAction->execute(
            actionNameResolver: $this->actionNameResolver,
            includeRouteClosures: false,
            filters: $this->filters,
        );

        $transformedEntities = $this->resolveTransformed($routeCollection);

        foreach ($transformedEntities as $transformed) {
            $transformedCollection->remove($transformed->reference);
            $transformed->setWriter($this->writer);
            $transformedCollection->add($transformed);
        }
    }
}
