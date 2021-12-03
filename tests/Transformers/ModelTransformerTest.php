<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\Transformers;

use Illuminate\Database\Schema\Blueprint;
use ReflectionClass;
use Schema;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\Model;
use Spatie\LaravelTypeScriptTransformer\Tests\TestCase;
use Spatie\LaravelTypeScriptTransformer\Transformers\ModelTransformer;

class ModelTransformerTest extends TestCase
{
    /** @test */
    public function it_Transform()
    {
        Schema::create('models', function(Blueprint $table){
            $table->increments('id');

            $table->string('title');

            $table->timestamps();
        });

        $transformer = new ModelTransformer();

        $transformed = $transformer->transform(new ReflectionClass(Model::class), 'Model');

        dd($transformed->transformed);
    }
}
