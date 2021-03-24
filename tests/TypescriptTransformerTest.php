<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests;

use Spatie\LaravelTypeScriptTransformer\Transformers\SpatieStateTransformer;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class TypescriptTransformerTest extends TestCase
{
    use MatchesSnapshots;

    private TemporaryDirectory $temporaryDirectory;

    public function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = (new TemporaryDirectory())->create();
    }

    /** @test */
    public function it_will_register_the_config_correctly()
    {
        config()->set('typescript-transformer.auto_discover_types', 'fake-searching-path');
        config()->set('typescript-transformer.transformers', [SpatieStateTransformer::class]);
        config()->set('typescript-transformer.output_file', 'index.d.ts');

        $config = resolve(TypeScriptTransformerConfig::class);

        $this->assertEquals(['fake-searching-path'], $config->getAutoDiscoverTypesPaths());
        $this->assertEquals([new SpatieStateTransformer()], $config->getTransformers());
        $this->assertEquals('index.d.ts', $config->getOutputFile());
    }

    /** @test */
    public function it_will_crash_if_an_older_version_of_searching_paths_was_defined()
    {
        config()->set('typescript-transformer.searching_path', 'fake-searching-path');
        config()->set('typescript-transformer.transformers', [SpatieStateTransformer::class]);
        config()->set('typescript-transformer.output_file', 'index.d.ts');

        $this->artisan('typescript:transform')->assertExitCode(1);
    }

    /** @test */
    public function it_can_transform_to_typescript()
    {
        config()->set('typescript-transformer.auto_discover_types', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->artisan('typescript:transform')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('index.d.ts'));
    }

    /** @test */
    public function it_can_define_the_input_path()
    {
        config()->set('typescript-transformer.searching_paths', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->artisan('typescript:transform --path='. __DIR__ . '/FakeClasses')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('index.d.ts'));
    }

    /** @test */
    public function it_can_define_a_relative_input_path()
    {
        config()->set('typescript-transformer.searching_paths', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->app->useAppPath(__DIR__);
        $this->app->setBasePath($this->temporaryDirectory->path('js'));

        $this->artisan('typescript:transform --path=FakeClasses')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('index.d.ts'));
    }

    /** @test */
    public function it_can_define_the_relative_output_path()
    {
        config()->set('typescript-transformer.searching_paths', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->app->useAppPath(__DIR__);
        $this->app->setBasePath($this->temporaryDirectory->path());

        $this->artisan('typescript:transform --path=FakeClasses --output=other-index.d.ts')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('resources/other-index.d.ts'));
    }
}
