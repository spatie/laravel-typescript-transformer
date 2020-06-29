<?php

namespace Spatie\LaravelTypescriptTransformer\Tests;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelTypescriptTransformer\TypescriptTransformerServiceProvider;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            TypescriptTransformerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
