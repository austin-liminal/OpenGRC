<?php

namespace App\Mcp\Tools;

use App\Mcp\EntityConfig;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateEntityTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Updates an existing entity in OpenGRC.

        Supported entity types:
        - `standard`: Compliance frameworks
        - `control`: Security controls
        - `implementation`: Control implementations
        - `policy`: Policies
        - `risk`: Risk entries
        - `program`: Security programs
        - `audit`: Audits
        - `audit_item`: Audit items
        - `vendor`: Vendors
        - `application`: Applications
        - `asset`: Assets

        Provide the entity `type`, `id`, and `data` containing the fields to update.
        Only provided fields will be updated; others remain unchanged.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'data' => 'required|array',
        ]);

        $type = $validated['type'];
        $config = EntityConfig::get($type);

        if (! $config) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Unknown entity type: {$type}",
                'available_types' => EntityConfig::types(),
            ], JSON_PRETTY_PRINT));
        }

        $modelClass = $config['model'];
        $entity = $modelClass::find($validated['id']);

        if (! $entity) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "{$config['label']} with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'];

        try {
            return DB::transaction(function () use ($entity, $config, $data) {
                // Filter data to only include allowed update fields
                $allowedFields = $config['update_fields'] ?? [];
                $filteredData = array_intersect_key($data, array_flip($allowedFields));

                if (empty($filteredData)) {
                    return Response::text(json_encode([
                        'success' => false,
                        'error' => 'No valid fields to update. Allowed fields: '.implode(', ', $allowedFields),
                    ], JSON_PRETTY_PRINT));
                }

                // Check for duplicate code if updating code field
                if ($config['code_field'] && isset($filteredData[$config['code_field']])) {
                    $newCode = $filteredData[$config['code_field']];
                    $modelClass = $config['model'];
                    $existing = $modelClass::where($config['code_field'], $newCode)
                        ->where('id', '!=', $entity->id)
                        ->exists();
                    if ($existing) {
                        return Response::text(json_encode([
                            'success' => false,
                            'error' => "A {$config['label']} with code '{$newCode}' already exists.",
                        ], JSON_PRETTY_PRINT));
                    }
                }

                // Update the entity
                $entity->update($filteredData);

                // Reload with relations
                if (! empty($config['detail_relations'])) {
                    $entity->load($config['detail_relations']);
                }

                $nameField = $config['name_field'] ?? 'name';
                $name = $entity->{$nameField} ?? $entity->{$config['code_field']} ?? "#{$entity->id}";

                return Response::text(json_encode([
                    'success' => true,
                    'message' => "{$config['label']} '{$name}' updated successfully.",
                    'updated_fields' => array_keys($filteredData),
                    Str::singular($config['plural']) => $this->formatUpdatedItem($entity, $config),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            });
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Failed to update {$config['label']}: ".$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Format updated item for response.
     *
     * @return array<string, mixed>
     */
    protected function formatUpdatedItem(mixed $item, array $config): array
    {
        $output = ['id' => $item->id];

        // Add code if available
        if ($config['code_field'] && isset($item->{$config['code_field']})) {
            $output[$config['code_field']] = $item->{$config['code_field']};
        }

        // Add name/title
        if ($config['name_field'] && isset($item->{$config['name_field']})) {
            $output[$config['name_field']] = $item->{$config['name_field']};
        }

        // Add all updatable fields
        foreach ($config['update_fields'] ?? [] as $field) {
            if (! isset($output[$field]) && isset($item->{$field})) {
                $value = $item->{$field};

                // Handle enums
                if (is_object($value) && method_exists($value, 'value')) {
                    $value = $value->value;
                }

                // Handle dates
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d');
                }

                $output[$field] = $value;
            }
        }

        // Add timestamps
        $output['updated_at'] = $item->updated_at->toIso8601String();

        // Add URL
        $output['url'] = url($config['url_path'].'/'.$item->id);

        return $output;
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(EntityConfig::types())
                ->description('The type of entity to update. Options: '.implode(', ', EntityConfig::types()))
                ->required(),

            'id' => $schema->integer()
                ->description('The database ID of the entity to update.')
                ->required(),

            'data' => $schema->object()
                ->description('The fields to update. Only provided fields will be changed.')
                ->required(),
        ];
    }
}
