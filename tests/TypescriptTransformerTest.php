<?php

use Spatie\LaravelTypeScriptTransformer\Transformers\SpatieStateTransformer;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\TypeScriptTransformer\Writers\ModuleWriter;

beforeEach(function () {
    $this->temporaryDirectory = (new TemporaryDirectory())->create();
});

it('will register the config correctly', function () {
    config()->set('typescript-transformer.auto_discover_types', 'fake-searching-path');
    config()->set('typescript-transformer.transformers', [SpatieStateTransformer::class]);
    config()->set('typescript-transformer.output_file', 'index.d.ts');
    config()->set('typescript-transformer.writer', ModuleWriter::class);

    $config = resolve(TypeScriptTransformerConfig::class);

    expect($config->getAutoDiscoverTypesPaths())->toEqual(['fake-searching-path']);
    expect($config->getTransformers())->toEqual([new SpatieStateTransformer()]);
    expect($config->getOutputFile())->toEqual('index.d.ts');
    expect($config->getWriter())->toBeInstanceOf(ModuleWriter::class);
});

it('will crash if an older version of searching paths was defined', function () {
    config()->set('typescript-transformer.searching_path', 'fake-searching-path');
    config()->set('typescript-transformer.transformers', [SpatieStateTransformer::class]);
    config()->set('typescript-transformer.output_file', 'index.d.ts');

    $this->artisan('typescript:transform')->assertExitCode(1);
});

it('can transform to typescript', function () {
    config()->set('typescript-transformer.auto_discover_types', __DIR__ . '/FakeClasses');
    config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

    $this->artisan('typescript:transform')->assertExitCode(0);

    expect($this->temporaryDirectory->path('index.d.ts'))->toMatchFileSnapshot();
});

it('can define the input path', function () {
    config()->set('typescript-transformer.searching_paths', __DIR__ . '/FakeClasses');
    config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

    $this->artisan('typescript:transform --path=' . __DIR__ . '/FakeClasses')->assertExitCode(0);

    expect($this->temporaryDirectory->path('index.d.ts'))->toMatchFileSnapshot();
});

it('can define a relative input path', function () {
    config()->set('typescript-transformer.searching_paths', __DIR__ . '/FakeClasses');
    config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

    $this->app->useAppPath(__DIR__);
    $this->app->setBasePath($this->temporaryDirectory->path('js'));

    $this->artisan('typescript:transform --path=FakeClasses')->assertExitCode(0);

    expect($this->temporaryDirectory->path('index.d.ts'))->toMatchFileSnapshot();
});

it('can define the relative output path', function () {
    config()->set('typescript-transformer.searching_paths', __DIR__ . '/FakeClasses');
    config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

    $this->app->useAppPath(__DIR__);
    $this->app->setBasePath($this->temporaryDirectory->path());

    $this->artisan('typescript:transform --path=FakeClasses --output=other-index.d.ts')->assertExitCode(0);

    expect($this->temporaryDirectory->path('resources/other-index.d.ts'))->toMatchFileSnapshot();
});

it('can check if output file is up to date without mutating it', function () {
    config()->set('typescript-transformer.auto_discover_types', __DIR__ . '/FakeClasses');

    $outputFile = $this->temporaryDirectory->path('index.d.ts');
    config()->set('typescript-transformer.output_file', $outputFile);

    $this->artisan('typescript:transform')->assertExitCode(0);

    $originalContent = file_get_contents($outputFile);
    $originalModTime = filemtime($outputFile);

    $this->artisan('typescript:transform --check-only')->assertExitCode(0);

    expect(file_get_contents($outputFile))->toBe($originalContent);
    expect(filemtime($outputFile))->toBe($originalModTime);
});

it('detects when output file is not up to date', function () {
    config()->set('typescript-transformer.auto_discover_types', __DIR__ . '/FakeClasses');

    $outputFile = $this->temporaryDirectory->path('index.d.ts');
    config()->set('typescript-transformer.output_file', $outputFile);

    file_put_contents($outputFile, '// Outdated content');
    $originalContent = file_get_contents($outputFile);
    $originalModTime = filemtime($outputFile);

    $this->artisan('typescript:transform --check-only')->assertExitCode(1);

    expect(file_get_contents($outputFile))->toBe($originalContent);
    expect(filemtime($outputFile))->toBe($originalModTime);
});

it('handles missing output file in check-only mode', function () {
    config()->set('typescript-transformer.auto_discover_types', __DIR__ . '/FakeClasses');

    $outputFile = $this->temporaryDirectory->path('index.d.ts');
    config()->set('typescript-transformer.output_file', $outputFile);

    if (file_exists($outputFile)) {
        unlink($outputFile);
    }

    $this->artisan('typescript:transform --check-only')->assertExitCode(1);

    expect(file_exists($outputFile))->toBeFalse();
});

it('does not change any files in check-only mode', function () {
    config()->set('typescript-transformer.auto_discover_types', __DIR__ . '/FakeClasses');

    $outputFile = $this->temporaryDirectory->path('index.d.ts');
    config()->set('typescript-transformer.output_file', $outputFile);

    // Create an extra file to ensure it's not touched
    $extraFile = $this->temporaryDirectory->path('extra.txt');
    file_put_contents($outputFile, '// Initial content');
    file_put_contents($extraFile, 'extra');

    $originalOutputContent = file_get_contents($outputFile);
    $originalOutputModTime = filemtime($outputFile);
    $originalExtraContent = file_get_contents($extraFile);
    $originalExtraModTime = filemtime($extraFile);

    $this->artisan('typescript:transform --check-only')->assertExitCode(1);

    expect(file_get_contents($outputFile))->toBe($originalOutputContent);
    expect(filemtime($outputFile))->toBe($originalOutputModTime);
    expect(file_get_contents($extraFile))->toBe($originalExtraContent);
    expect(filemtime($extraFile))->toBe($originalExtraModTime);
});
