<?php

use Spatie\TypeScriptTransformer\Enums\RunnerMode;
use Spatie\TypeScriptTransformer\Runners\Runner;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

beforeEach(function () {
    app()->instance(TypeScriptTransformerConfig::class, Mockery::mock(TypeScriptTransformerConfig::class));
});

it('shows an error when the config is not published', function () {
    app()->forgetInstance(TypeScriptTransformerConfig::class);

    $this->artisan('typescript:transform')
        ->expectsOutputToContain('Please, first publish the TypeScriptTransformerServiceProvider and configure it.')
        ->assertExitCode(1);
});

it('runs in direct mode by default', function () {
    $runner = Mockery::mock(Runner::class);
    $runner->shouldReceive('run')
        ->once()
        ->withArgs(fn ($logger, $config, $mode) => $mode === RunnerMode::Direct)
        ->andReturn(0);

    app()->instance(Runner::class, $runner);

    $this->artisan('typescript:transform')
        ->assertExitCode(0);
});

it('runs in master mode with --watch', function () {
    $runner = Mockery::mock(Runner::class);
    $runner->shouldReceive('run')
        ->once()
        ->withArgs(fn ($logger, $config, $mode) => $mode === RunnerMode::Master)
        ->andReturn(0);

    app()->instance(Runner::class, $runner);

    $this->artisan('typescript:transform --watch')
        ->assertExitCode(0);
});

it('runs in worker mode with --watch and --worker', function () {
    $runner = Mockery::mock(Runner::class);
    $runner->shouldReceive('run')
        ->once()
        ->withArgs(fn ($logger, $config, $mode) => $mode === RunnerMode::Worker)
        ->andReturn(0);

    app()->instance(Runner::class, $runner);

    $this->artisan('typescript:transform --watch --worker')
        ->assertExitCode(0);
});

it('throws when --worker is used without --watch', function () {
    $this->artisan('typescript:transform --worker');
})->throws(Exception::class, 'A worker only needs to be started in watch mode.');