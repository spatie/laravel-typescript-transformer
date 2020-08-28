<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\Fakes;

use ReflectionType;

class FakeReflectionType extends ReflectionType
{
    private string $type;

    public static function create(): self
    {
        return new self();
    }

    public function __construct()
    {
    }

    public function withType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->type;
    }
}
