<?php

use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelControllerAction;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\InvokableController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ResourceController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\TypedController;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;

it('produces a LaravelController from a PhpClassNode', function () {
    $classNode = PhpClassNode::fromClassString(TypedController::class);
    $action = new ResolveLaravelControllerAction();

    $result = $action->execute($classNode);

    expect($result)->toBeInstanceOf(LaravelController::class);
    expect($result->fqcn)->toBe(TypedController::class);
    expect($result->filePath)->toContain('TypedController.php');
});

it('only includes public methods declared on the class', function () {
    $classNode = PhpClassNode::fromClassString(TypedController::class);
    $action = new ResolveLaravelControllerAction();

    $result = $action->execute($classNode);

    expect($result->methods)->toHaveKeys(['index', 'show', 'store']);
    expect($result->methods)->not->toHaveKey('protectedMethod');
    expect($result->methods)->not->toHaveKey('privateMethod');
});

it('resolves native return types', function () {
    $classNode = PhpClassNode::fromClassString(TypedController::class);
    $action = new ResolveLaravelControllerAction();

    $result = $action->execute($classNode);

    expect($result->methods['index']['response'])->not->toBeNull();
    expect($result->methods['store']['response'])->not->toBeNull();
});

it('resolves docblock return types over native types', function () {
    $classNode = PhpClassNode::fromClassString(TypedController::class);
    $action = new ResolveLaravelControllerAction();

    $result = $action->execute($classNode);

    // `show()` has @return array<string, int> docblock - should be resolved from docblock
    expect($result->methods['show']['response'])->not->toBeNull();
});

it('handles a controller with no typed methods', function () {
    $classNode = PhpClassNode::fromClassString(InvokableController::class);
    $action = new ResolveLaravelControllerAction();

    $result = $action->execute($classNode);

    expect($result)->toBeInstanceOf(LaravelController::class);
    expect($result->fqcn)->toBe(InvokableController::class);
    expect($result->methods)->toHaveKey('__invoke');
    expect($result->methods['__invoke']['response'])->toBeNull();
    expect($result->methods['__invoke']['request'])->toBeNull();
});

it('handles a resource controller', function () {
    $classNode = PhpClassNode::fromClassString(ResourceController::class);
    $action = new ResolveLaravelControllerAction();

    $result = $action->execute($classNode);

    expect($result)->toBeInstanceOf(LaravelController::class);
    expect($result->methods)->toHaveKeys(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
});

it('sets request type to null when no BaseData parameter exists', function () {
    $classNode = PhpClassNode::fromClassString(TypedController::class);
    $action = new ResolveLaravelControllerAction();

    $result = $action->execute($classNode);

    foreach ($result->methods as $method) {
        expect($method['request'])->toBeNull();
    }
});
