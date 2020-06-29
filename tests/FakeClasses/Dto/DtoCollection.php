<?php

namespace Spatie\LaravelTypescriptTransformer\Tests\FakeClasses\Dto;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class DtoCollection extends DataTransferObjectCollection
{
    public function current(): OtherDto
    {
        return parent::current();
    }
}
