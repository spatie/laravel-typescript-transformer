<?php

namespace Spatie\LaravelTypeScriptTransformer\Actions;

use ReflectionMethod;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelController;
use Spatie\TypeScriptTransformer\Actions\TranspilePhpStanTypeToTypeScriptNodeAction;
use Spatie\TypeScriptTransformer\Actions\TranspilePhpTypeNodeToTypeScriptNodeAction;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpMethodNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpNamedTypeNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpParameterNode;
use Spatie\TypeScriptTransformer\TypeResolvers\DocTypeResolver;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;

class ResolveLaravelControllerAction
{
    public function __construct(
        protected DocTypeResolver $docTypeResolver = new DocTypeResolver(),
        protected TranspilePhpStanTypeToTypeScriptNodeAction $transpilePhpStanAction = new TranspilePhpStanTypeToTypeScriptNodeAction(),
        protected TranspilePhpTypeNodeToTypeScriptNodeAction $transpilePhpTypeAction = new TranspilePhpTypeNodeToTypeScriptNodeAction(),
    ) {
    }

    public function execute(PhpClassNode $classNode): LaravelController
    {
        $methods = [];

        foreach ($classNode->getMethods(ReflectionMethod::IS_PUBLIC) as $methodNode) {
            if ($methodNode->reflection->getDeclaringClass()->getName() !== $classNode->getName()) {
                continue;
            }

            $methods[$methodNode->getName()] = [
                'response' => $this->resolveResponseType($classNode, $methodNode),
                'request' => $this->resolveRequestType($classNode, $methodNode),
            ];
        }

        return new LaravelController(
            fqcn: $classNode->getName(),
            filePath: $classNode->getFileName(),
            methods: $methods,
        );
    }

    protected function resolveResponseType(PhpClassNode $classNode, PhpMethodNode $methodNode): ?TypeScriptNode
    {
        $annotation = $this->docTypeResolver->method($methodNode);

        if ($annotation?->returnType) {
            return $this->transpilePhpStanAction->execute($annotation->returnType, $classNode);
        }

        $returnType = $methodNode->getReturnType();

        if ($returnType) {
            return $this->transpilePhpTypeAction->execute($returnType, $classNode);
        }

        return null;
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
