<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\InvokableController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ResourceController;
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelControllerTransformedProvider;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;

it('generates correct TypeScript output for controllers', function () {
    $router = app(Router::class);
    $router->setRoutes(new RouteCollection());

    $router->get('invokable', InvokableController::class);
    $router->resource('resource', ResourceController::class);

    $provider = new LaravelControllerTransformedProvider(
        actionNameResolver: new StrippedActionNameResolver([
            'Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses' => null,
        ]),
    );

    $transformed = $provider->provide();

    // Should have 3 transformed items: support, InvokableController, ResourceController
    expect($transformed)->toHaveCount(3);

    $transformedCollection = new TransformedCollection();

    foreach ($transformed as $item) {
        $transformedCollection->add($item);
    }

    [$writer] = $transformedCollection->getUniqueWriters();

    $files = $writer->output($transformed, $transformedCollection);

    // Check that we have files
    expect($files)->not->toBeEmpty();

    // Check the file content - ModuleWriter puts all items with same location in one file
    $contents = $files[0]->contents;
    expect($contents)->toContain('createActionWithMethods');
    expect($contents)->toContain('InvokableController');
    expect($contents)->toContain('ResourceController');
})->skip('LaravelControllerTransformedProvider implementation is incomplete');

it('generates snapshot output for controllers', function () {
    $router = app(Router::class);
    $router->setRoutes(new RouteCollection());

    $router->get('invokable', InvokableController::class);
    $router->resource('resource', ResourceController::class);

    $provider = new LaravelControllerTransformedProvider(
        actionNameResolver: new StrippedActionNameResolver([
            'Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses' => null,
        ]),
    );

    $transformed = $provider->provide();

    $transformedCollection = new TransformedCollection();

    foreach ($transformed as $item) {
        $transformedCollection->add($item);
    }

    [$writer] = $transformedCollection->getUniqueWriters();

    $files = $writer->output($transformed, $transformedCollection);

    // ModuleWriter combines all transformed items in one file for the 'controllers' location
    expect($files)->toHaveCount(1);
    expect($files[0]->contents)->toMatchSnapshot();
})->skip('LaravelControllerTransformedProvider implementation is incomplete');
