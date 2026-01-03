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
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Creates a new entity in OpenGRC.

        Supported entity types:
        - `standard`: Compliance frameworks (code, name, authority, description required)
        - `control`: Security controls (code, title, standard_id required)
        - `implementation`: Control implementations (details required)
        - `policy`: Policies (name required, code auto-generated if not provided)
        - `risk`: Risk entries (name, likelihood, impact required)
        - `program`: Security programs (name required)
        - `audit`: Audits (title required)
        - `audit_item`: Audit items (title, audit_id required)
        - `vendor`: Vendors (name required)
        - `application`: Applications (name required)
        - `asset`: Assets (name required)

        The `data` parameter should contain the fields for the entity.
        Use ListEntities or GetEntity first to understand available fields.
    MARKDOWN;

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

        // Build and apply validation rules for the data
        $rules = EntityConfig::createValidationRules($type);
        $prefixedRules = [];
        foreach ($rules as $field => $rule) {
            $prefixedRules["data.{$field}"] = $rule;
        }

        try {
            $request->validate($prefixedRules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Validation failed',
                'validation_errors' => $e->errors(),
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'];

        try {
            return DB::transaction(function () use ($type, $config, $data) {
                $modelClass = $config['model'];

                // Handle auto-code generation for policies
                if ($type === 'policy' && empty($data['code'])) {
                    $data['code'] = $this->generateUniqueCode($modelClass, 'POL');
                }

                // Check for duplicate code if code field exists
                if ($config['code_field'] && ! empty($data[$config['code_field']])) {
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
     */
    protected function generateUniqueCode(string $modelClass, string $prefix): string
    {
        $pattern = $prefix.'-%';
        $lastEntity = $modelClass::where('code', 'like', $pattern)
            ->orderByRaw('CAST(SUBSTRING(code, '.(strlen($prefix) + 2).') AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($lastEntity && preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $lastEntity->code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s-%03d', $prefix, $nextNumber);
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
