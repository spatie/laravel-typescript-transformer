<?php

use Spatie\LaravelTypeScriptTransformer\ActionNameResolvers\StrippedActionNameResolver;

it('resolves controller names correctly', function (array $prefixes, string $input, string $expected) {
    $resolver = new StrippedActionNameResolver($prefixes);

    expect($resolver->resolve($input))->toBe($expected);
})->with([
    'no prefixes configured' => [
        'prefixes' => [],
        'input' => 'App\\Http\\Controllers\\UserController',
        'expected' => '/App/Http/Controllers/UserController',
    ],
    'no matching prefix' => [
        'prefixes' => ['App\\Http\\Controllers' => null],
        'input' => 'Vendor\\Package\\SomeController',
        'expected' => '/Vendor/Package/SomeController',
    ],
    'strip prefix with null replacement' => [
        'prefixes' => ['App\\Http\\Controllers' => null],
        'input' => 'App\\Http\\Controllers\\UserController',
        'expected' => 'UserController',
    ],
    'strip prefix keeps nested namespaces' => [
        'prefixes' => ['App\\Http\\Controllers' => null],
        'input' => 'App\\Http\\Controllers\\Api\\UserController',
        'expected' => 'Api/UserController',
    ],
    'replace prefix with custom value' => [
        'prefixes' => ['App\\Http\\Controllers' => 'Http'],
        'input' => 'App\\Http\\Controllers\\UserController',
        'expected' => 'Http/UserController',
    ],
    'replace prefix keeps nested namespaces' => [
        'prefixes' => ['App\\Http\\Controllers' => 'Http'],
        'input' => 'App\\Http\\Controllers\\Api\\UserController',
        'expected' => 'Http/Api/UserController',
    ],
]);
