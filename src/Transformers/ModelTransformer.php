<?php

namespace App\Transformers;

use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ModelTransformer implements Transformer
{
    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        if (! is_subclass_of($class->name, Model::class)) {
            return null;
        }
        /** @var Model $modelInstance */
        $modelInstance = $class->newInstanceWithoutConstructor();

        $table = $modelInstance->getTable();
        $hidden = $modelInstance->getHidden();
        $casts = $modelInstance->getCasts();

        $columns = Schema::getColumns($table);
        $columnNames = array_map(fn ($col) => $col['name'], $columns);

        $serializedColumnNames = array_diff($columnNames, $hidden);

        $typescriptProperties = [];

        foreach ($serializedColumnNames as $index => $propertyName) {
            $column = $columns[$index];
            $isNullable = $column['nullable'];
            $typescriptType = $this->mapTypeNameToJsonType($column['type_name']);

            if (array_key_exists($propertyName, $casts)) {
                // TODO: Get the typescript type for the $cast.
            }

            $typescriptPropertyDefinition = "$propertyName: $typescriptType";

            if ($isNullable) {
                $typescriptPropertyDefinition .= ' | null';
            }

            $typescriptProperties[] = $typescriptPropertyDefinition;
        }

        return TransformedType::create(
            $class,
            $name,
            "{\n".implode("\n", $typescriptProperties)."\n}",
        );
    }

    private function mapTypeNameToJsonType(string $columnType): string
    {
        // Map Laravel column types to TypeScript types
        return match ($columnType) {
            // Strings
            'string', 'text', 'varchar', 'character varying', 'date', 'datetime', 'timestamp', 'timestamp without time zone' => 'string',
            // Numbers
            'integer', 'bigint', 'int4', 'int8', 'float', 'double', 'decimal' => 'number',
            // Booleans
            'boolean', 'bool' => 'boolean',
            // Unknown
            default => 'unknown', // Fallback for other types
        };
    }
}
