<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            TypeScriptTransformerServiceProvider::class,
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
