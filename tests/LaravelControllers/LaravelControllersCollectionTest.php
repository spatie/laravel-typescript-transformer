<?php

use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelController;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelControllersCollection;

beforeEach(function () {
    $this->collection = new LaravelControllersCollection();
});

it('can add and get a controller', function () {
    $controller = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __FILE__,
        methods: [],
    );

    $this->collection->add($controller);

    expect($this->collection->get('App\Http\Controllers\UserController'))->toBe($controller);
    expect($this->collection->has('App\Http\Controllers\UserController'))->toBeTrue();
    expect($this->collection)->toHaveCount(1);
});

it('returns null for unknown fqcn', function () {
    expect($this->collection->get('App\Http\Controllers\Unknown'))->toBeNull();
    expect($this->collection->has('App\Http\Controllers\Unknown'))->toBeFalse();
});

it('can remove a controller by fqcn', function () {
    $controller = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __FILE__,
        methods: [],
    );

    $this->collection->add($controller);
    $this->collection->remove('App\Http\Controllers\UserController');

    expect($this->collection->has('App\Http\Controllers\UserController'))->toBeFalse();
    expect($this->collection->findByFile(__FILE__))->toBeNull();
    expect($this->collection)->toHaveCount(0);
});

it('can find a controller by file path', function () {
    $controller = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __FILE__,
        methods: [],
    );

    $this->collection->add($controller);

    expect($this->collection->findByFile(__FILE__))->toBe($controller);
});

it('returns null when finding by unknown file path', function () {
    expect($this->collection->findByFile('/nonexistent/path.php'))->toBeNull();
});

it('can remove a controller by file path', function () {
    $controller = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __FILE__,
        methods: [],
    );

    $this->collection->add($controller);
    $this->collection->removeByFile(__FILE__);

    expect($this->collection->has('App\Http\Controllers\UserController'))->toBeFalse();
    expect($this->collection)->toHaveCount(0);
});

it('can remove controllers by directory', function () {
    $controller1 = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __DIR__ . '/LaravelControllersCollectionTest.php',
        methods: [],
    );

    $controller2 = new LaravelController(
        fqcn: 'App\Http\Controllers\PostController',
        filePath: __DIR__ . '/../TestCase.php',
        methods: [],
    );

    $this->collection->add($controller1);
    $this->collection->add($controller2);

    $this->collection->removeByDirectory(__DIR__);

    expect($this->collection->has('App\Http\Controllers\UserController'))->toBeFalse();
    expect($this->collection->has('App\Http\Controllers\PostController'))->toBeTrue();
    expect($this->collection)->toHaveCount(1);
});

it('replaces existing controller with same fqcn on add', function () {
    $controller1 = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __FILE__,
        methods: ['index' => ['response' => null, 'request' => null]],
    );

    $controller2 = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __FILE__,
        methods: ['show' => ['response' => null, 'request' => null]],
    );

    $this->collection->add($controller1);
    $this->collection->add($controller2);

    expect($this->collection)->toHaveCount(1);
    expect($this->collection->get('App\Http\Controllers\UserController'))->toBe($controller2);
});

it('is iterable', function () {
    $controller1 = new LaravelController(
        fqcn: 'App\Http\Controllers\UserController',
        filePath: __FILE__,
        methods: [],
    );

    $controller2 = new LaravelController(
        fqcn: 'App\Http\Controllers\PostController',
        filePath: __DIR__ . '/../TestCase.php',
        methods: [],
    );

    $this->collection->add($controller1);
    $this->collection->add($controller2);

    $items = iterator_to_array($this->collection);

    expect($items)->toHaveCount(2);
    expect($items['App\Http\Controllers\UserController'])->toBe($controller1);
    expect($items['App\Http\Controllers\PostController'])->toBe($controller2);
});

it('handles removing non-existent fqcn gracefully', function () {
    $this->collection->remove('App\Http\Controllers\Unknown');

    expect($this->collection)->toHaveCount(0);
});

it('handles removing by non-existent file gracefully', function () {
    $this->collection->removeByFile('/nonexistent/path.php');

    expect($this->collection)->toHaveCount(0);
});
