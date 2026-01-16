<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\InvokableController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ResourceController;
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelRouteTransformedProvider;
use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;

it('generates correct TypeScript output for named routes', function () {
    $router = app(Router::class);
    $router->setRoutes(new RouteCollection());

    $router->get('/', fn () => 'home')->name('home');
    $router->get('pricing', fn () => 'pricing')->name('pricing');
    $router->get('blog', fn () => 'blog')->name('blog');
    $router->get('blog/{slug}', fn () => 'blog.show')->name('blog.show');
    $router->get('{project}/errors', fn () => 'projects.show')->name('projects.show');
    $router->get('errors/{error}/{errorOccurrence}', fn () => 'occurrences.show')->name('occurrences.show');
    $router->get('users/{id?}', fn () => 'users.show')->name('users.show');
    $router->get('invokable', InvokableController::class)->name('invokable');
    $router->resource('resource', ResourceController::class);

    $provider = new LaravelRouteTransformedProvider(
        absoluteUrlsByDefault: true,
    );

    $transformed = $provider->provide(
        TypeScriptTransformerConfigFactory::create()->outputDirectory(sys_get_temp_dir())->get()
    );

    $transformedCollection = new TransformedCollection();

    foreach ($transformed as $item) {
        $transformedCollection->add($item);
    }

    [$writer] = $transformedCollection->getUniqueWriters();

    $files = $writer->output($transformed, $transformedCollection);

    expect($files[0]->contents)->toMatchSnapshot();
});
