<?php

namespace App\Mcp\Resources;

use App\Mcp\EntityConfig;
use Illuminate\Support\Str;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

/**
 * MCP Resource for entity schema descriptions.
 *
 * Provides read-only access to entity type schemas including
 * field types, constraints, and relation information.
 * URI format: opengrc://schema/{type}
 *
 * @example opengrc://schema/policy
 * @example opengrc://schema/control
 */
#[Audience(Role::User)]
class SchemaResource extends Resource implements HasUriTemplate
{
    /**
     * The resource's name.
     */
    protected string $name = 'schema';

    /**
     * The resource's description.
     */
    protected string $description = 'Describes the schema and available fields for a GRC entity type. Use this to understand what fields are available before creating or updating entities.';

    /**
     * The resource's MIME type.
     */
    protected string $mimeType = 'application/json';

    /**
     * Get the URI template for this resource.
     */
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('opengrc://schema/{type}');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $type = $request->get('type');

        $config = EntityConfig::get($type);

        if (! $config) {
            return Response::error(json_encode([
                'success' => false,
                'error' => "Unknown entity type: {$type}",
                'available_types' => EntityConfig::types(),
            ], JSON_PRETTY_PRINT));
        }

        $modelClass = $config['model'];
        $instance = new $modelClass;
        $casts = $instance->getCasts();

        // Build field descriptions
        $fields = $this->describeFields($config, $casts);

        // Build relation descriptions
        $relations = $this->describeRelations($config, $instance);

        $result = [
            'success' => true,
            'type' => $type,
            'label' => $config['label'],
            'description' => "Schema for {$config['label']} entities",
            'fields' => $fields,
            'relations' => $relations,
            'notes' => $this->buildNotes($config, $type),
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Describe all fields with their types and constraints.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function describeFields(array $config, array $casts): array
    {
        $fields = [];
        $createFields = $config['create_fields'] ?? [];
        $fieldDescriptions = $config['field_descriptions'] ?? [];

        foreach ($createFields as $field => $fieldConfig) {
            $fieldInfo = [
                'type' => $fieldConfig['type'] ?? 'string',
                'required' => $fieldConfig['required'] ?? false,
                'description' => $fieldDescriptions[$field] ?? Str::title(str_replace('_', ' ', $field)),
            ];

            // Add max length for strings
            if (isset($fieldConfig['max'])) {
                $fieldInfo['max_length'] = $fieldConfig['max'];
            }

            // Check if this is an enum field
            $cast = $casts[$field] ?? null;
            if ($cast && class_exists($cast) && enum_exists($cast)) {
                $fieldInfo['type'] = 'enum';
                $fieldInfo['values'] = $this->getEnumValues($cast);
            }

            // Add foreign key info
            if (isset($fieldConfig['exists'])) {
                $fieldInfo['references'] = $fieldConfig['exists'];
            }

            $fields[$field] = $fieldInfo;
        }

        return $fields;
    }

    /**
     * Get enum values from an enum class.
     *
     * @return array<string>
     */
    protected function getEnumValues(string $enumClass): array
    {
        $values = [];
        foreach ($enumClass::cases() as $case) {
            $values[] = $case->value ?? $case->name;
        }

        return $values;
    }

    /**
     * Describe relations available on the entity.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function describeRelations(array $config, mixed $instance): array
    {
        $relations = [];

        // Describe BelongsTo relations (for list_relations)
        foreach ($config['list_relations'] ?? [] as $relation) {
            if (method_exists($instance, $relation)) {
                $relations[$relation] = [
                    'type' => 'belongs_to',
                    'description' => 'Parent '.Str::title(str_replace('_', ' ', Str::snake($relation))),
                ];
            }
        }

        // Describe HasMany/BelongsToMany relations (for list_counts)
        foreach ($config['list_counts'] ?? [] as $relation) {
            if (method_exists($instance, $relation)) {
                $relations[$relation] = [
                    'type' => 'has_many',
                    'description' => 'Collection of related '.Str::title(str_replace('_', ' ', Str::snake($relation))),
                ];
            }
        }

        // Add any additional detail relations not already covered
        foreach ($config['detail_relations'] ?? [] as $relation) {
            if (! isset($relations[$relation]) && method_exists($instance, $relation)) {
                $relations[$relation] = [
                    'type' => 'relation',
                    'description' => 'Related '.Str::title(str_replace('_', ' ', Str::snake($relation))),
                ];
            }
        }

        return $relations;
    }

    /**
     * Build helpful notes about this entity type.
     *
     * @return array<string>
     */
    protected function buildNotes(array $config, string $type): array
    {
        $notes = [];

        // Note about code field
        if ($config['code_field']) {
            if (isset($config['auto_code_prefix'])) {
                $notes[] = "Codes are auto-generated with prefix '{$config['auto_code_prefix']}' if not provided.";
            } else {
                $notes[] = "The '{$config['code_field']}' field can be used to look up entities by code.";
            }
        }

        // Note about HTML fields
        $htmlFields = ['body', 'purpose', 'policy_scope', 'details', 'description'];
        $modelHtmlFields = array_intersect(array_keys($config['create_fields'] ?? []), $htmlFields);
        if (! empty($modelHtmlFields)) {
            $notes[] = 'HTML is supported in: '.implode(', ', $modelHtmlFields);
        }

        // Note about URL
        $notes[] = 'View in app: '.url($config['url_path']);

        return $notes;
    }
}
