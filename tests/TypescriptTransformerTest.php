<?php

namespace Spatie\LaravelTypescriptTransformer\Tests;

use Spatie\LaravelTypescriptTransformer\Transformers\SpatieEnumTransformer;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\TypescriptTransformer\TypeScriptTransformerConfig;

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
        config()->set('typescript-transformer.searching_path', 'fake-searching-path');
        config()->set('typescript-transformer.transformers', [SpatieEnumTransformer::class]);
        config()->set('typescript-transformer.output_file', 'index.d.ts');

        $config = resolve(TypeScriptTransformerConfig::class);

        $this->assertEquals('fake-searching-path', $config->getSearchingPath());
        $this->assertEquals([new SpatieEnumTransformer()], $config->getTransformers());
        $this->assertEquals('index.d.ts', $config->getOutputFile());
    }

    /** @test */
    public function it_can_transform_to_typescript()
    {
        config()->set('typescript-transformer.searching_path', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->artisan('typescript:transform')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('index.d.ts'));
    }

    /** @test */
    public function it_can_define_the_input_path()
    {
        config()->set('typescript-transformer.searching_path', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->artisan('typescript:transform --class='. __DIR__ . '/FakeClasses/Enum.php ')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('index.d.ts'));
    }

    /** @test */
    public function it_can_define_a_relative_input_path()
    {
        config()->set('typescript-transformer.searching_path', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->app->useAppPath(__DIR__);
        $this->app->setBasePath($this->temporaryDirectory->path('js'));

        $this->artisan('typescript:transform --class=FakeClasses/Enum.php')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('index.d.ts'));
    }

    /** @test */
    public function it_can_define_the_relative_output_path()
    {
        config()->set('typescript-transformer.searching_path', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->app->useAppPath(__DIR__);
        $this->app->setBasePath($this->temporaryDirectory->path());

        $this->artisan('typescript:transform --class=FakeClasses/Enum.php --output=other-index.d.ts')->assertExitCode(0);

        $this->assertMatchesFileSnapshot($this->temporaryDirectory->path('resources/other-index.d.ts'));
    }
}
