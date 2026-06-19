<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveRouteCollectionAction;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\ControllerRouteFilter;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\NamedRouteFilter;
use Spatie\LaravelTypeScriptTransformer\RouteFilters\RouteFilter;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteCollection;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteController;
use Spatie\LaravelTypeScriptTransformer\Routes\RouteControllerAction;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\InvokableController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ResourceController;

it('can resolve all possible routes', function (Closure $route, Closure $expectations) {
    $router = app(Router::class);

    $router->setRoutes(new \Illuminate\Routing\RouteCollection());

    $route($router);

    $routes = app(ResolveRouteCollectionAction::class)->execute(
        true
    );

    $expectations($routes);
})->with(function () {
    yield 'simple closure' => [
        fn (Router $router) => $router->get('simple', fn () => 'simple'),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toBeEmpty();
            expect($routes->closures)->toHaveCount(1);

            expect($routes->closures['Closure(simple)']->url)->toBe('simple');
            expect($routes->closures['Closure(simple)']->methods)->toBe(['GET', 'HEAD']);
        },
    ];
    yield 'root closure' => [
        fn (Router $router) => $router->get('/', fn () => 'home'),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toBeEmpty();
            expect($routes->closures)->toHaveCount(1);

            expect($routes->closures['Closure(/)']->url)->toBe('');
            expect($routes->closures['Closure(/)']->methods)->toBe(['GET', 'HEAD']);
        },
    ];
    yield 'controller action' => [
        fn (Router $router) => $router->get('action', [ResourceController::class, 'update']),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toHaveCount(1);
            expect($routes->closures)->toBeEmpty();

            $actions = Arr::keyBy($routes->controllers[ResourceController::class]->actions, 'methodName');

            expect($actions)->toHaveCount(1);
            expect($actions['update'])->toBeInstanceOf(RouteControllerAction::class);
            expect($actions['update']->methodName)->toBe('update');
            expect($actions['update']->url)->toBe('action');
            expect($actions['update']->methods)->toBe(['GET', 'HEAD']);

            expect($actions['update']->parameters)->toBeArray();
            expect($actions['update']->parameters)->toBeEmpty();
        },
    ];
    yield 'invokable controller' => [
        fn (Router $router) => $router->get('invokable', InvokableController::class),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toHaveCount(1);
            expect($routes->closures)->toBeEmpty();

            $controller = $routes->controllers[InvokableController::class];

            expect($controller)->toBeInstanceOf(RouteController::class);
            expect($controller->invokable)->toBeTrue();
            expect($controller->class)->toBe(InvokableController::class);

            expect($controller->actions)->toHaveCount(1);

            $action = Arr::keyBy($controller->actions, 'methodName')['__invoke'];
            expect($action)->toBeInstanceOf(RouteControllerAction::class);
            expect($action->methodName)->toBe('__invoke');
            expect($action->url)->toBe('invokable');
            expect($action->methods)->toBe(['GET', 'HEAD']);

            expect($action->parameters)->toBeArray();
            expect($action->parameters)->toBeEmpty();
        },
    ];
    yield 'resource controller' => [
        fn (Router $router) => $router->resource('resource', ResourceController::class),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toHaveCount(1);
            expect($routes->closures)->toBeEmpty();

            $controller = $routes->controllers[ResourceController::class];

            expect($controller)->toBeInstanceOf(RouteController::class);
            expect($controller->invokable)->toBeFalse();
            expect($controller->class)->toBe(ResourceController::class);
            expect($controller->actions)->toHaveCount(7);

            $controller->actions = Arr::keyBy($controller->actions, 'methodName');

            expect($controller->actions['index'])->toBeInstanceOf(RouteControllerAction::class);
            expect($controller->actions['index']->methodName)->toBe('index');
            expect($controller->actions['index']->url)->toBe('resource');
            expect($controller->actions['index']->methods)->toBe(['GET', 'HEAD']);
            expect($controller->actions['index']->parameters)->toBeEmpty();

            expect($controller->actions['create'])->toBeInstanceOf(RouteControllerAction::class);
            expect($controller->actions['create']->methodName)->toBe('create');
            expect($controller->actions['create']->url)->toBe('resource/create');
            expect($controller->actions['create']->methods)->toBe(['GET', 'HEAD']);
            expect($controller->actions['create']->parameters)->toBeEmpty();

            expect($controller->actions['store'])->toBeInstanceOf(RouteControllerAction::class);
            expect($controller->actions['store']->methodName)->toBe('store');
            expect($controller->actions['store']->url)->toBe('resource');
            expect($controller->actions['store']->methods)->toBe(['POST']);
            expect($controller->actions['store']->parameters)->toBeEmpty();

            expect($controller->actions['show'])->toBeInstanceOf(RouteControllerAction::class);
            expect($controller->actions['show']->methodName)->toBe('show');
            expect($controller->actions['show']->url)->toBe('resource/{resource}');
            expect($controller->actions['show']->methods)->toBe(['GET', 'HEAD']);
            expect($controller->actions['show']->parameters)->toHaveCount(1);

            expect($controller->actions['edit'])->toBeInstanceOf(RouteControllerAction::class);
            expect($controller->actions['edit']->methodName)->toBe('edit');
            expect($controller->actions['edit']->url)->toBe('resource/{resource}/edit');
            expect($controller->actions['edit']->methods)->toBe(['GET', 'HEAD']);
            expect($controller->actions['edit']->parameters)->toHaveCount(1);

            expect($controller->actions['update'])->toBeInstanceOf(RouteControllerAction::class);
            expect($controller->actions['update']->methodName)->toBe('update');
            expect($controller->actions['update']->url)->toBe('resource/{resource}');
            expect($controller->actions['update']->methods)->toBe(['PUT', 'PATCH']);
            expect($controller->actions['update']->parameters)->toHaveCount(1);

            expect($controller->actions['destroy'])->toBeInstanceOf(RouteControllerAction::class);
            expect($controller->actions['destroy']->methodName)->toBe('destroy');
            expect($controller->actions['destroy']->url)->toBe('resource/{resource}');
            expect($controller->actions['destroy']->methods)->toBe(['DELETE']);
            expect($controller->actions['destroy']->parameters)->toHaveCount(1);
        },
    ];
    yield 'nested' => [
        fn (Router $router) => $router->group(['prefix' => 'nested'], fn (Router $router) => $router->get('simple', fn () => 'simple')),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toBeEmpty();
            expect($routes->closures)->toHaveCount(1);

            expect($routes->closures['Closure(nested/simple)']->url)->toBe('nested/simple');
            expect($routes->closures['Closure(nested/simple)']->methods)->toBe(['GET', 'HEAD']);
        },
    ];
    yield 'methods' => [
        function (Router $router) {
            $router->get('get', fn () => 'get');
            $router->post('post', fn () => 'post');
            $router->put('put', fn () => 'put');
            $router->patch('patch', fn () => 'patch');
            $router->delete('delete', fn () => 'delete');
            $router->options('options', fn () => 'options');
        },
        function (RouteCollection $routes) {
            expect($routes->controllers)->toBeEmpty();
            expect($routes->closures)->toHaveCount(6);

            expect($routes->closures['Closure(get)']->methods)->toBe(['GET', 'HEAD']);
            expect($routes->closures['Closure(post)']->methods)->toBe(['POST']);
            expect($routes->closures['Closure(put)']->methods)->toBe(['PUT']);
            expect($routes->closures['Closure(patch)']->methods)->toBe(['PATCH']);
            expect($routes->closures['Closure(delete)']->methods)->toBe(['DELETE']);
            expect($routes->closures['Closure(options)']->methods)->toBe(['OPTIONS']);
        },
    ];
    yield 'parameter' => [
        fn (Router $router) => $router->get('simple/{id}', fn () => 'simple'),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toBeEmpty();
            expect($routes->closures)->toHaveCount(1);

            expect($routes->closures['Closure(simple/{id})']->url)->toBe('simple/{id}');
            expect($routes->closures['Closure(simple/{id})']->methods)->toBe(['GET', 'HEAD']);
            expect($routes->closures['Closure(simple/{id})']->parameters)->toHaveCount(1);
            expect($routes->closures['Closure(simple/{id})']->parameters[0]['name'])->toBe('id');
            expect($routes->closures['Closure(simple/{id})']->parameters[0]['optional'])->toBeFalse();
        },
    ];
    yield 'nullable parameter' => [
        fn (Router $router) => $router->get('simple/{id?}', fn () => 'simple'),
        function (RouteCollection $routes) {
            expect($routes->controllers)->toBeEmpty();
            expect($routes->closures)->toHaveCount(1);

            expect($routes->closures['Closure(simple/{id?})']->url)->toBe('simple/{id}');
            expect($routes->closures['Closure(simple/{id?})']->methods)->toBe(['GET', 'HEAD']);
            expect($routes->closures['Closure(simple/{id?})']->parameters)->toHaveCount(1);
            expect($routes->closures['Closure(simple/{id?})']->parameters[0]['name'])->toBe('id');
            expect($routes->closures['Closure(simple/{id?})']->parameters[0]['optional'])->toBeTrue();
        },
    ];
    yield 'named routes' => [
        function (Router $router) {
            $router->get('simple', fn () => 'simple')->name('simple');
            $router->get('invokable', InvokableController::class)->name('invokable');
            $router->resource('resource', ResourceController::class);
        },
        function (RouteCollection $routes) {
            expect($routes->controllers)->toHaveCount(2);
            expect($routes->closures)->toHaveCount(1);

            expect($routes->closures['Closure(simple)']->name)->toBe('simple');

            $invokableController = $routes->controllers[InvokableController::class];
            $invokableActions = Arr::keyBy($invokableController->actions, 'methodName');
            expect($invokableActions['__invoke']->name)->toBe('invokable');

            $resourceController = $routes->controllers[ResourceController::class];
            $resourceController->actions = Arr::keyBy($resourceController->actions, 'methodName');

            expect($resourceController->actions['index']->name)->toBe('resource.index');
            expect($resourceController->actions['show']->name)->toBe('resource.show');
            expect($resourceController->actions['create']->name)->toBe('resource.create');
            expect($resourceController->actions['update']->name)->toBe('resource.update');
            expect($resourceController->actions['store']->name)->toBe('resource.store');
            expect($resourceController->actions['edit']->name)->toBe('resource.edit');
            expect($resourceController->actions['destroy']->name)->toBe('resource.destroy');
        },
    ];
    yield 'multiple routes sharing an invokable controller' => [
        function (Router $router) {
            $router->get('terms', InvokableController::class)->name('terms');
            $router->get('privacy', InvokableController::class)->name('privacy');
            $router->get('cookies', InvokableController::class)->name('cookies');
        },
        function (RouteCollection $routes) {
            expect($routes->controllers)->toHaveCount(1);

            $controller = $routes->controllers[InvokableController::class];

            expect($controller->invokable)->toBeTrue();
            expect($controller->actions)->toHaveCount(3);
            expect(Arr::pluck($controller->actions, 'name'))
                ->toBe(['terms', 'privacy', 'cookies']);
            expect(Arr::pluck($controller->actions, 'url'))
                ->toBe(['terms', 'privacy', 'cookies']);
        },
    ];
    yield 'multiple routes sharing a controller method' => [
        function (Router $router) {
            $router->get('en/about', [ResourceController::class, 'show'])->name('en.about');
            $router->get('nl/about', [ResourceController::class, 'show'])->name('nl.about');
        },
        function (RouteCollection $routes) {
            expect($routes->controllers)->toHaveCount(1);

            $controller = $routes->controllers[ResourceController::class];

            expect($controller->invokable)->toBeFalse();
            expect($controller->actions)->toHaveCount(2);
            expect(Arr::pluck($controller->actions, 'methodName'))
                ->toBe(['show', 'show']);
            expect(Arr::pluck($controller->actions, 'name'))
                ->toBe(['en.about', 'nl.about']);
        },
    ];
});

it('can filter out certain routes', function (
    RouteFilter $filter,
    Closure $expectations
) {
    $router = app(Router::class);

    $router->setRoutes(new \Illuminate\Routing\RouteCollection());

    $router->get('simple', fn () => 'simple')->name('simple');
    $router->get('invokable', InvokableController::class)->name('invokable');
    $router->resource('resource', ResourceController::class);

    $routes = app(ResolveRouteCollectionAction::class)->execute(
        true,
        [$filter]
    );

    $expectations($routes);
})->with(function () {
    yield 'named' => [
        new NamedRouteFilter('simple'),
        function (RouteCollection $routes) {
            expect($routes->closures)->toBeEmpty();
            expect($routes->controllers)->toHaveCount(2);
        },
    ];
    yield 'multiple named' => [
        new NamedRouteFilter('simple', 'resource.index', 'resource.edit'),
        function (RouteCollection $routes) {
            expect($routes->closures)->toBeEmpty();
            expect($routes->controllers)
                ->toHaveCount(2)
                ->toHaveKeys([
                    ResourceController::class,
                    InvokableController::class,
                ]);
            expect(Arr::keyBy($routes->controllers[ResourceController::class]->actions, 'methodName'))
                ->toHaveCount(5)
                ->toHaveKeys([
                    'show',
                    'create',
                    'update',
                    'store',
                    'destroy',
                ]);
        },
    ];
    yield 'wildcard name' => [
        new NamedRouteFilter('invokable', 'resource.*'),
        function (RouteCollection $routes) {
            expect($routes->closures)->toHaveCount(1);
            expect($routes->controllers)->toHaveCount(0);
        },
    ];
    yield 'controller' => [
        new ControllerRouteFilter(ResourceController::class),
        function (RouteCollection $routes) {
            expect($routes->closures)->toHaveCount(1);
            expect($routes->controllers)->toHaveCount(1)->toHaveKey(InvokableController::class);
        },
    ];
    yield 'multiple controllers' => [
        new ControllerRouteFilter(ResourceController::class),
        function (RouteCollection $routes) {
            expect($routes->closures)->toHaveCount(1);
            expect($routes->controllers)->toHaveCount(1)->toHaveKey(InvokableController::class);
        },
    ];
    yield 'controller wildcard' => [
        new ControllerRouteFilter('Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\*'),
        function (RouteCollection $routes) {
            expect($routes->closures)->toHaveCount(1);
            expect($routes->controllers)->toHaveCount(0);
        },
    ];
    yield 'controller action' => [
        new ControllerRouteFilter([ResourceController::class, 'index'], [ResourceController::class, 'edit']),
        function (RouteCollection $routes) {
            expect($routes->closures)->toHaveCount(1);
            expect($routes->controllers)
                ->toHaveCount(2)
                ->toHaveKeys([
                    ResourceController::class,
                    InvokableController::class,
                ]);
            expect(Arr::keyBy($routes->controllers[ResourceController::class]->actions, 'methodName'))
                ->toHaveCount(5)
                ->toHaveKeys([
                    'show',
                    'create',
                    'update',
                    'store',
                    'destroy',
                ]);
        },
    ];
});
