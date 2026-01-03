<?php

namespace App\Mcp\Traits;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait for models to provide MCP (Model Context Protocol) support.
 *
 * This trait auto-discovers model configuration from:
 * - $fillable array for create/update fields
 * - $casts array for field types
 * - Relationship methods via reflection
 * - Conventions for common patterns
 *
 * Models can override specific config by defining mcpConfig() method.
 */
trait HasMcpSupport
{
    /**
     * Get the complete MCP configuration for this model.
     *
     * @return array<string, mixed>
     */
    public static function getMcpConfig(): array
    {
        $instance = new static;
        $defaults = static::buildDefaultMcpConfig($instance);
        $overrides = method_exists(static::class, 'mcpConfig') ? static::mcpConfig() : [];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build default MCP config from model introspection.
     *
     * @return array<string, mixed>
     */
    protected static function buildDefaultMcpConfig(self $instance): array
    {
        $className = class_basename(static::class);
        $fillable = $instance->getFillable();
        $casts = $instance->getCasts();

        return [
            'model' => static::class,
            'label' => static::deriveLabel($className),
            'plural' => static::derivePlural($className),
            'code_field' => static::deriveCodeField($fillable),
            'name_field' => static::deriveNameField($fillable),
            'search_fields' => static::deriveSearchFields($fillable, $casts),
            'list_fields' => static::deriveListFields($fillable, $casts),
            'list_relations' => static::deriveListRelations($instance),
            'list_counts' => static::deriveListCounts($instance),
            'detail_relations' => static::deriveDetailRelations($instance),
            'create_fields' => static::deriveCreateFields($fillable, $casts),
            'update_fields' => static::deriveUpdateFields($fillable),
            'url_path' => static::deriveUrlPath($className),
        ];
    }

    /**
     * Derive human-readable label from class name.
     */
    protected static function deriveLabel(string $className): string
    {
        // AuditItem -> Audit Item
        return implode(' ', preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Derive plural form for JSON keys.
     */
    protected static function derivePlural(string $className): string
    {
        return Str::snake(Str::pluralStudly($className));
    }

    /**
     * Derive code field if model has one.
     */
    protected static function deriveCodeField(array $fillable): ?string
    {
        return in_array('code', $fillable) ? 'code' : null;
    }

    /**
     * Derive name/title field.
     */
    protected static function deriveNameField(array $fillable): ?string
    {
        foreach (['name', 'title'] as $field) {
            if (in_array($field, $fillable)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Derive searchable fields from fillable string fields.
     */
    protected static function deriveSearchFields(array $fillable, array $casts): array
    {
        $searchable = [];
        $textFields = ['name', 'title', 'code', 'description', 'details', 'notes', 'body', 'purpose'];

        foreach ($fillable as $field) {
            // Include common text fields
            if (in_array($field, $textFields)) {
                $searchable[] = $field;

                continue;
            }

            // Skip ID fields and non-text casts
            if (Str::endsWith($field, '_id')) {
                continue;
            }
            if (isset($casts[$field]) && ! in_array($casts[$field], ['string', 'text'])) {
                continue;
            }
        }

        return $searchable;
    }

    /**
     * Derive list fields (subset of fillable for list views).
     */
    protected static function deriveListFields(array $fillable, array $casts): array
    {
        $listFields = ['id'];
        $priority = ['code', 'name', 'title', 'status', 'description', 'type', 'category'];

        // Add priority fields first
        foreach ($priority as $field) {
            if (in_array($field, $fillable)) {
                $listFields[] = $field;
            }
        }

        // Add date fields
        foreach ($fillable as $field) {
            if (Str::contains($field, 'date') && ! in_array($field, $listFields)) {
                $listFields[] = $field;
            }
        }

        return $listFields;
    }

    /**
     * Derive relations to load for list views (belongsTo only).
     */
    protected static function deriveListRelations(self $instance): array
    {
        $relations = [];
        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== static::class) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }

            $typeName = $returnType->getName();

            // Only include BelongsTo for list views (parent references)
            if ($typeName === 'Illuminate\Database\Eloquent\Relations\BelongsTo') {
                $methodName = $method->getName();
                // Exclude common audit fields
                if (! in_array($methodName, ['creator', 'updater', 'createdBy', 'updatedBy'])) {
                    $relations[] = $methodName;
                }
            }
        }

        return $relations;
    }

    /**
     * Derive relations to count for list views (hasMany, belongsToMany).
     */
    protected static function deriveListCounts(self $instance): array
    {
        $counts = [];
        $reflection = new ReflectionClass($instance);
        $countableTypes = [
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== static::class) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }

            if (in_array($returnType->getName(), $countableTypes)) {
                $counts[] = $method->getName();
            }
        }

        return $counts;
    }

    /**
     * Derive relations to load for detail views (all relations).
     */
    protected static function deriveDetailRelations(self $instance): array
    {
        $relations = [];
        $reflection = new ReflectionClass($instance);
        $relationTypes = [
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\HasOne',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== static::class) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }

            if (in_array($returnType->getName(), $relationTypes)) {
                $relations[] = $method->getName();
            }
        }

        return $relations;
    }

    /**
     * Derive create fields with type info from fillable and casts.
     */
    protected static function deriveCreateFields(array $fillable, array $casts): array
    {
        $fields = [];
        $requiredFields = ['name', 'title', 'code'];
        $excludeFromCreate = ['created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($fillable as $field) {
            if (in_array($field, $excludeFromCreate)) {
                continue;
            }

            $fieldConfig = static::deriveFieldType($field, $casts[$field] ?? null);

            // Mark common required fields
            if (in_array($field, $requiredFields)) {
                $fieldConfig['required'] = true;
            }

            $fields[$field] = $fieldConfig;
        }

        return $fields;
    }

    /**
     * Derive field type configuration.
     *
     * @return array<string, mixed>
     */
    protected static function deriveFieldType(string $field, ?string $cast): array
    {
        // Handle _id fields as integer references
        if (Str::endsWith($field, '_id')) {
            $table = Str::plural(Str::beforeLast($field, '_id'));

            return [
                'type' => 'integer',
                'exists' => "{$table},id",
            ];
        }

        // Handle based on cast
        $type = match ($cast) {
            'integer', 'int' => 'integer',
            'float', 'double', 'decimal' => 'number',
            'boolean', 'bool' => 'boolean',
            'date', 'datetime', 'immutable_date', 'immutable_datetime' => 'date',
            'array', 'json', 'object', 'collection' => 'array',
            default => null,
        };

        if ($type) {
            return ['type' => $type];
        }

        // Handle based on field name patterns
        if (Str::contains($field, ['email'])) {
            return ['type' => 'email'];
        }

        if (Str::contains($field, ['url', 'website', 'link'])) {
            return ['type' => 'url'];
        }

        if (Str::contains($field, ['phone', 'fax', 'mobile'])) {
            return ['type' => 'string', 'max' => 50];
        }

        if (Str::contains($field, ['body', 'description', 'details', 'notes', 'content', 'purpose', 'scope'])) {
            return ['type' => 'text'];
        }

        // Default to string with max length
        return ['type' => 'string', 'max' => 255];
    }

    /**
     * Derive update fields (same as fillable minus audit fields).
     */
    protected static function deriveUpdateFields(array $fillable): array
    {
        $exclude = ['created_by', 'updated_by'];

        return array_values(array_diff($fillable, $exclude));
    }

    /**
     * Derive URL path from class name.
     */
    protected static function deriveUrlPath(string $className): string
    {
        return '/app/'.Str::kebab(Str::pluralStudly($className));
    }
}
