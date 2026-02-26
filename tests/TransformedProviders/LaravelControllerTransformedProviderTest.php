<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\GenerateControllerSupportAction;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\InvokableController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ResourceController;
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelControllerTransformedProvider;
use Spatie\TypeScriptTransformer\Actions\ConnectReferencesAction;
use Spatie\TypeScriptTransformer\Collections\PhpNodeCollection;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\Support\Loggers\NullLogger;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProviderActions;

beforeEach(function () {
    $reflection = new ReflectionClass(GenerateControllerSupportAction::class);
    $property = $reflection->getProperty('cachedSupport');
    $property->setValue(null, null);
});

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

    // Should have 15 transformed items: 11 support + 2 per controller (const + namespace)
    expect($transformed)->toHaveCount(15);

    $transformedCollection = new TransformedCollection();

    foreach ($transformed as $item) {
        $transformedCollection->add($item);
    }

    (new ConnectReferencesAction(new NullLogger()))->execute($transformedCollection);

    [$writer] = $transformedCollection->getUniqueWriters();

    $files = $writer->output($transformed, $transformedCollection);

    $allContents = implode("\n", array_map(fn ($file) => $file->contents, $files));

    expect($allContents)->toContain('createActionWithMethods');
    expect($allContents)->toContain('InvokableController');
    expect($allContents)->toContain('ResourceController');
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

    (new ConnectReferencesAction(new NullLogger()))->execute($transformedCollection);

    [$writer] = $transformedCollection->getUniqueWriters();

    $files = $writer->output($transformed, $transformedCollection);

    expect($files)->toHaveCount(1);

    $allContents = implode("\n---\n", array_map(
        fn ($file) => "// {$file->path}\n{$file->contents}",
        $files,
    ));

    expect($allContents)->toMatchSnapshot();
});
