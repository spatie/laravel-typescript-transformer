<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\InvokableController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ResourceController;
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelControllerTransformedProvider;
use Spatie\TypeScriptTransformer\Collections\PhpNodeCollection;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProviderActions;

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

    $provider->setPhpNodeCollection(new PhpNodeCollection());
    $provider->setActions(new TransformedProviderActions());

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

    // Check the file content
    $contents = $files[0]->contents;
    expect($contents)->toContain('createActionWithMethods');
    expect($contents)->toContain('InvokableController');
    expect($contents)->toContain('ResourceController');
});

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

    $provider->setPhpNodeCollection(new PhpNodeCollection());
    $provider->setActions(new TransformedProviderActions());

    $transformed = $provider->provide();

    $transformedCollection = new TransformedCollection();

    foreach ($transformed as $item) {
        $transformedCollection->add($item);
    }

    [$writer] = $transformedCollection->getUniqueWriters();

    $files = $writer->output($transformed, $transformedCollection);

    expect($files)->toHaveCount(1);
    expect($files[0]->contents)->toMatchSnapshot();
});
