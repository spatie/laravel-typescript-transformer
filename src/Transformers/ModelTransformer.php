<?php

namespace Spatie\LaravelTypeScriptTransformer\Transformers;

use Artisan;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\String_;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;

class ModelTransformer implements Transformer
{
    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        if (! $class->isSubclassOf(Model::class)) {
            return null;
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $class->newInstance();

        Artisan::call(ModelsCommand::class);

        dd($model->getCasts());

        $columns = $model->getConnection()->getDoctrineSchemaManager()->listTableColumns($model->getTable());

        $missingSymbols = new MissingSymbolsCollection();

        $transformed = join(PHP_EOL, array_map(
            fn(Column $column) => $this->resolveColumnType($column, $missingSymbols),
            $columns
        ));

        return TransformedType::create(
            $class,
            $name,
            "{{$transformed}}",
            $missingSymbols
        );
    }

    private function resolveColumnType(Column $column, MissingSymbolsCollection $missingSymbolsCollection)
    {
        return "{$column->getName()}:{$this->mapColumnType($column->getType())};";
    }

    private function mapColumnType(Type $type)
    {
        return match ($type->getName()) {
            'integer', 'smallint', 'bigint' => new Integer(),
            'float', 'decimal' => new Float_(),
            'string', 'ascii_string', 'text', 'guid', 'binary', 'blob', 'date', 'date_immutable', 'datetime', 'datetime_immutable', 'datetimetz', 'datetimetz_immutable', 'time', 'time_immutable', 'date°interval' => new String_(),
            'boolean' => new Boolean(),
        };
    }
}
