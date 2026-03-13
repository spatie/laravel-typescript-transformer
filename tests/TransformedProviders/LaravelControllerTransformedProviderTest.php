<?php

use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;
use Spatie\LaravelTypeScriptTransformer\Actions\GenerateControllerSupportAction;
use Spatie\LaravelTypeScriptTransformer\References\LaravelControllerReference;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\InvokableController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ResourceController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\TypedController;
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelControllerTransformedProvider;
use Spatie\TypeScriptTransformer\Actions\ConnectReferencesAction;
use Spatie\TypeScriptTransformer\Collections\PhpNodeCollection;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\Support\Loggers\NullLogger;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProviderActions;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;

it('generates correct TypeScript output for controllers', function () {
    $router = app('router');

    $router->get('invokable', InvokableController::class);
    $router->resource('resource', ResourceController::class);

    expect(transformControllers())->toMatchSnapshot();
});

it('resolves native PHP return types', function () {
    $router = app('router');

    $router->get('typed', [TypedController::class, 'returnsPhpType']);

    expect(transformControllers())->toMatchSnapshot();
});

it('resolves array shape return types', function () {
    $router = app('router');

    $router->get('shaped', [TypedController::class, 'returnsArrayShape']);

    expect(transformControllers())->toMatchSnapshot();
});

it('resolves void return type as object', function () {
    $router = app('router');

    $router->get('void', [TypedController::class, 'returnsVoid']);

    expect(transformControllers())->toMatchSnapshot();
});

it('produces only support code when no routes are registered', function () {
    expect(transformControllers())->toMatchSnapshot();
});

it('handles optional route parameters', function () {
    $router = app('router');

    $router->get('users/{user}/{slug?}', [TypedController::class, 'returnsPhpType']);

    expect(transformControllers())->toMatchSnapshot();
});

it('sorts HTTP methods in correct order', function () {
    $router = app('router');

    $router->match(['delete', 'get', 'post'], 'multi', [TypedController::class, 'returnsPhpType']);

    expect(transformControllers())->toMatchSnapshot();
});

it('omits type parameters when action has no route parameters', function () {
    $router = app('router');

    $router->get('simple', [TypedController::class, 'returnsPhpType']);

    expect(transformControllers())->toMatchSnapshot();
});

it('includes type parameters when action has route parameters', function () {
    $router = app('router');

    $router->get('users/{user}', [TypedController::class, 'returnsPhpType']);

    expect(transformControllers())->toMatchSnapshot();
});

function transformControllers(): string
{
    $provider = new LaravelControllerTransformedProvider(
        actionNameResolver: new StrippedActionNameResolver([
            'Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses' => null,
        ]),
        generateSupportAction: new class extends GenerateControllerSupportAction {
            public function execute(): array
            {
                return [
                    new Transformed(
                        new TypeScriptIdentifier('createActionWithMethods'),
                        LaravelControllerReference::supportItem('createActionWithMethods'),
                        [],
                        true,
                    ),
                ];
            }
        }
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

    return implode("\n", array_map(fn ($file) => $file->contents, $files));
}
