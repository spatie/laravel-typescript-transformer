<?php

namespace Spatie\LaravelTypeScriptTransformer\Transformers;

use Spatie\TypeScriptTransformer\Structures\TransformedType;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

/**
 * A Transformer that generates Typescript definitions for a Model by inspecting the DB Schema
 */
class ModelTransformer implements Transformer
{
    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        if (!is_subclass_of($class->name, Model::class)) {
            return null;
        }

        /** @var Model $modelInstance */
        $modelInstance = $class->newInstanceWithoutConstructor();

        $table = $modelInstance->getTable();
        $columns = Schema::getColumnListing($table);

        $hidden = $modelInstance->getHidden();

        $serializedColumns = array_diff($columns, $hidden);

        $column_defs = collect(DB::select("
            SELECT column_name, is_nullable, data_type
            FROM information_schema.columns
            WHERE table_name = '$table';
        "));

        $model_attrs = [];
        foreach ($serializedColumns as $column) {
            $def = $column_defs->firstWhere('column_name', $column);
            $is_nullable = $def->is_nullable == 'YES';
            $column_type = $this->mapToTypeScriptType($def->data_type);
            $attr_type = "$column: $column_type";

            if ($is_nullable) {
                $attr_type .= ' | null';
            }

            $model_attrs[] = $attr_type;
        }

        return TransformedType::create(
            $class,
            $name,
            '{'.implode("\n", $model_attrs).'}',
        );
    }

    /**
     *  Map column types to TypeScript types
     */
    private function mapToTypeScriptType(string $data_type): string
    {
        return match ($data_type) {
            'string', 'text', 'varchar', 'character varying' => 'string',
            'integer', 'bigint', 'int8' => 'number',
            'float', 'double', 'decimal' => 'number',
            'boolean', 'bool' => 'boolean',
            'date', 'datetime', 'timestamp', 'timestamp without time zone' => 'Date',
            default => dd($columnType), // Fallback for other types
        };
    }
}
