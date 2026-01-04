<?php

namespace App\Mcp\Tools;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Mcp\EntityConfig;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateEntityTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'CreateEntity';

    /**
     * The tool's description.
     */
    protected string $description = 'Creates a new GRC entity. Use GetEntity on existing items to see available fields. Policy codes are auto-generated if not provided.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'required|string',
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

        $data = $validated['data'];

        // Track if we need to auto-generate code inside the transaction
        $needsAutoCode = $type === 'policy' && empty($data['code']);

        // Build and apply validation rules for the data
        $rules = EntityConfig::createValidationRules($type);
        $prefixedRules = [];
        foreach ($rules as $field => $rule) {
            // Skip code validation if we're auto-generating it
            if ($field === 'code' && $needsAutoCode) {
                continue;
            }
            $prefixedRules["data.{$field}"] = $rule;
        }

        try {
            validator($validated, $prefixedRules)->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Validation failed',
                'validation_errors' => $e->errors(),
            ], JSON_PRETTY_PRINT));
        }

        try {
            return DB::transaction(function () use ($type, $config, $data, $needsAutoCode) {
                $modelClass = $config['model'];

                // Auto-generate code inside transaction with locking to prevent race conditions
                if ($needsAutoCode) {
                    $data['code'] = $this->generateUniqueCode($modelClass, 'POL');
                }

                // Check for duplicate code if code field exists (for user-provided codes)
                if (! $needsAutoCode && $config['code_field'] && ! empty($data[$config['code_field']])) {
                    $code = $data[$config['code_field']];
                    if ($modelClass::where($config['code_field'], $code)->exists()) {
                        return Response::text(json_encode([
                            'success' => false,
                            'error' => "A {$config['label']} with code '{$code}' already exists.",
                        ], JSON_PRETTY_PRINT));
                    }
                }

                // Set default status for policies
                if ($type === 'policy' && ! isset($data['status_id'])) {
                    $draftStatus = Taxonomy::where('type', 'policy-status')
                        ->where('name', 'Draft')
                        ->first();
                    if ($draftStatus) {
                        $data['status_id'] = $draftStatus->id;
                    }
                }

                // Filter data to only include allowed create fields
                $allowedFields = array_keys($config['create_fields'] ?? []);
                $filteredData = array_intersect_key($data, array_flip($allowedFields));

                // Create the entity
                $entity = $modelClass::create($filteredData);

                // Load relations for response
                if (! empty($config['detail_relations'])) {
                    $entity->load($config['detail_relations']);
                }

                $nameField = $config['name_field'] ?? 'name';
                $name = $entity->{$nameField} ?? $entity->{$config['code_field']} ?? "#{$entity->id}";

                return Response::text(json_encode([
                    'success' => true,
                    'message' => "{$config['label']} '{$name}' created successfully.",
                    Str::singular($config['plural']) => $this->formatCreatedItem($entity, $config),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            });
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Failed to create {$config['label']}: ".$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Generate a unique code for an entity.
     * Must be called within a transaction for proper locking.
     */
    protected function generateUniqueCode(string $modelClass, string $prefix): string
    {
        $pattern = $prefix.'-%';

        // Use lockForUpdate to prevent race conditions when multiple requests
        // try to generate codes simultaneously within concurrent transactions.
        // Include soft-deleted records to avoid unique constraint violations.
        $query = $modelClass::where('code', 'like', $pattern)->lockForUpdate();

        // Include soft-deleted records if the model uses SoftDeletes
        if (method_exists($modelClass, 'withTrashed')) {
            $query = $modelClass::withTrashed()->where('code', 'like', $pattern)->lockForUpdate();
        }

        // Fetch all matching codes and find the max number in PHP
        // This is database-agnostic (works with SQLite, MySQL, PostgreSQL)
        $codes = $query->pluck('code');

        $maxNumber = 0;
        $patternRegex = '/^'.preg_quote($prefix, '/').'-(\d+)$/';

        foreach ($codes as $code) {
            if (preg_match($patternRegex, $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        return sprintf('%s-%03d', $prefix, $maxNumber + 1);
    }

    /**
     * Format created item for response.
     *
     * @return array<string, mixed>
     */
    protected function formatCreatedItem(mixed $item, array $config): array
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

        // Add other created fields
        foreach (array_keys($config['create_fields'] ?? []) as $field) {
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
        $output['created_at'] = $item->created_at->toIso8601String();

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
                ->description('The type of entity to create. Options: '.implode(', ', EntityConfig::types()))
                ->required(),

            'data' => $schema->object()
                ->description('The data for the new entity. Fields vary by type. Use GetEntity on existing items to see available fields.')
                ->required(),
        ];
    }
}
