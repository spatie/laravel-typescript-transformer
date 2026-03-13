<?php

namespace Spatie\LaravelTypeScriptTransformer\Actions;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\CursorPaginatedDataCollection;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\TypeScriptTransformer\Actions\TranspilePhpStanTypeToTypeScriptNodeAction;
use Spatie\TypeScriptTransformer\Actions\TranspilePhpTypeNodeToTypeScriptNodeAction;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpMethodNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpNamedTypeNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpParameterNode;
use Spatie\TypeScriptTransformer\References\ClassStringReference;
use Spatie\TypeScriptTransformer\TypeResolvers\DocTypeResolver;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptArray;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptBoolean;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptGeneric;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNull;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNumber;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObject;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptReference;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;
use Symfony\Component\HttpFoundation\Response;

class ResolveLaravelControllerMethodAction
{
    public function __construct(
        protected DocTypeResolver $docTypeResolver = new DocTypeResolver(),
        protected TranspilePhpStanTypeToTypeScriptNodeAction $transpilePhpStanAction = new TranspilePhpStanTypeToTypeScriptNodeAction(),
        protected TranspilePhpTypeNodeToTypeScriptNodeAction $transpilePhpTypeAction = new TranspilePhpTypeNodeToTypeScriptNodeAction(),
    ) {
    }

    /**
     * @return array{
     *     request: ?TypeScriptNode,
     *     response: ?TypeScriptNode
     * }
     */
    public function execute(PhpClassNode $classNode, string $methodName): array
    {
        if (! $classNode->hasMethod($methodName)) {
            return ['request' => null, 'response' => null];
        }

        $methodNode = $classNode->getMethod($methodName);

        return [
            'response' => $this->resolveResponseType($classNode, $methodNode),
            'request' => $this->resolveRequestType($classNode, $methodNode),
        ];
    }

    protected function resolveResponseType(PhpClassNode $classNode, PhpMethodNode $methodNode): ?TypeScriptNode
    {
        $annotation = $this->docTypeResolver->method($methodNode);

        if ($annotation?->returnType) {
            return $this->filterResponseType(
                $this->transpilePhpStanAction->execute($annotation->returnType, $classNode)
            );
        }

        $returnType = $methodNode->getReturnType();

        if ($returnType) {
            return $this->filterResponseType(
                $this->transpilePhpTypeAction->execute($returnType, $classNode)
            );
        }

        return null;
    }

    protected function filterResponseType(TypeScriptNode $node): ?TypeScriptNode
    {
        if ($wrappedNode = $this->getNodeWrappedInResponse($node)) {
            $node = $wrappedNode;
        }

        if ($node instanceof TypeScriptString
            || $node instanceof TypeScriptNumber
            || $node instanceof TypeScriptBoolean
            || $node instanceof TypeScriptNull
            || $node instanceof TypeScriptArray
            || $node instanceof TypeScriptObject
        ) {
            return $node;
        }

        if ($node instanceof TypeScriptReference && $this->isDataReference($node)) {
            return $node;
        }

        if ($node instanceof TypeScriptGeneric && $this->isValidGenericResponse($node)) {
            return $node;
        }

        return null;
    }

    protected function getNodeWrappedInResponse(TypeScriptNode $node): ?TypeScriptNode
    {
        if (! $node instanceof TypeScriptGeneric
            || ! $node->type instanceof TypeScriptReference
            || ! $node->type->reference instanceof ClassStringReference
        ) {
            return null;
        }

        if (count($node->genericTypes) !== 1) {
            return null;
        }

        $class = $node->type->reference->classString;

        if (! is_subclass_of($class, Response::class) && $class !== 'Inertia\Response') {
            return null;
        }

        return $node->genericTypes[0];
    }

    protected function isDataReference(TypeScriptNode $node): bool
    {
        return  $node instanceof TypeScriptReference
            && $node->reference instanceof ClassStringReference
            && is_subclass_of($node->reference->classString, BaseData::class);
    }

    protected function isValidGenericResponse(TypeScriptGeneric $node): bool
    {
        if ($node->type instanceof TypeScriptIdentifier) {
            return true;
        }

        if (! $node->type instanceof TypeScriptReference
            || ! $node->type->reference instanceof ClassStringReference
        ) {
            return false;
        }

        $class = $node->type->reference->classString;

        $isCollection = is_a($class, Collection::class, true)
            || in_array($class, [
                DataCollection::class,
                PaginatedDataCollection::class,
                CursorPaginatedDataCollection::class,
            ]);

        if (! $isCollection) {
            return false;
        }

        $innerType = end($node->genericTypes);

        if ($innerType === false) {
            return false;
        }

        return $innerType instanceof TypeScriptObject
            || ($innerType instanceof TypeScriptReference && $this->isDataReference($innerType));
    }

    protected function resolveRequestType(PhpClassNode $classNode, PhpMethodNode $methodNode): ?TypeScriptNode
    {
        if (! interface_exists(BaseData::class)) {
            return null;
        }

        foreach ($methodNode->getParameters() as $parameterNode) {
            $requestType = $this->resolveDataParameter($classNode, $parameterNode);

            if ($requestType !== null) {
                return $requestType;
            }
        }

        return null;
    }

    protected function resolveDataParameter(PhpClassNode $classNode, PhpParameterNode $parameterNode): ?TypeScriptNode
    {
        if (! $parameterNode->hasType()) {
            return null;
        }

        $type = $parameterNode->getType();

        if (! $type instanceof PhpNamedTypeNode) {
            return null;
        }

        $className = $type->getName();

        if (! class_exists($className)) {
            return null;
        }

        if (! is_subclass_of($className, BaseData::class)) {
            return null;
        }

        return $this->transpilePhpTypeAction->execute($type, $classNode);
    }
}
