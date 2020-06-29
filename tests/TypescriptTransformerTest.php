<?php

namespace Spatie\LaravelTypescriptTransformer\Tests;

use Spatie\LaravelTypescriptTransformer\Transformers\EnumTransformer;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\TypescriptTransformer\TypeScriptTransformerConfig;

class TypescriptTransformerTest extends TestCase
{
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
        config()->set('typescript-transformer.transformers', [EnumTransformer::class]);
        config()->set('typescript-transformer.output_file', 'index.d.ts');

        $config = resolve(TypeScriptTransformerConfig::class);

        $this->assertEquals('fake-searching-path', $config->getSearchingPath());
        $this->assertEquals([EnumTransformer::class], $config->getTransformers());
        $this->assertEquals('index.d.ts', $config->getOutputFile());
    }

    /** @test */
    public function it_can_transform_to_typescript()
    {
        config()->set('typescript-transformer.searching_path', __DIR__ . '/FakeClasses');
        config()->set('typescript-transformer.output_file', $this->temporaryDirectory->path('index.d.ts'));

        $this->artisan('typescript:transform')->assertExitCode(0);

        $this->assertCount(3, scandir($this->temporaryDirectory->path(''))); // ., .. and generated.d.ts
        $this->assertEquals('index.d.ts', scandir($this->temporaryDirectory->path(''))[2]);
    }
}
