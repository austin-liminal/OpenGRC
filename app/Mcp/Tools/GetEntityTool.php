<?php

namespace App\Mcp\Tools;

use App\Mcp\EntityConfig;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetEntityTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'GetEntity';

    /**
     * The tool's description.
     */
    protected string $description = 'Retrieves a GRC entity by ID or code. Returns complete details with relations.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'id' => 'nullable|integer',
            'code' => 'nullable|string|max:255',
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

        if (empty($validated['id']) && empty($validated['code'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'You must provide either an id or code parameter.',
            ], JSON_PRETTY_PRINT));
        }

        $modelClass = $config['model'];
        $query = $modelClass::query();

        // Load relations for detail view
        if (! empty($config['detail_relations'])) {
            $query->with($config['detail_relations']);
        }

        // Find by ID or code
        if (! empty($validated['id'])) {
            $entity = $query->find($validated['id']);
        } elseif (! empty($validated['code']) && $config['code_field']) {
            $entity = $query->where($config['code_field'], $validated['code'])->first();
        } else {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Entity type '{$type}' does not support lookup by code. Use id instead.",
            ], JSON_PRETTY_PRINT));
        }

        if (! $entity) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "{$config['label']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $result = [
            'success' => true,
            'type' => $type,
            'label' => $config['label'],
            Str::singular($config['plural']) => $this->formatDetailItem($entity, $config),
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Format a single item for detail output.
     *
     * @return array<string, mixed>
     */
    protected function formatDetailItem(mixed $item, array $config): array
    {
        $output = [];

        // Get all fillable attributes plus id and timestamps
        $attributes = array_merge(
            ['id'],
            $item->getFillable(),
            ['created_at', 'updated_at']
        );

        foreach ($attributes as $field) {
            if (! isset($item->{$field})) {
                continue;
            }

            $value = $item->{$field};

            // Handle enums
            if (is_object($value) && method_exists($value, 'value')) {
                $value = $value->value;
            }

            // Handle dates
            if ($value instanceof \DateTimeInterface) {
                $value = $value->toIso8601String();
            }

            $output[$field] = $value;
        }

        // Add relation data
        foreach ($config['detail_relations'] as $relation) {
            $related = $item->{$relation};
            $relatedName = Str::snake($relation);

            if ($related === null) {
                continue;
            }

            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                $output[$relatedName] = $related->map(function ($r) use ($config) {
                    return $this->formatRelatedItem($r, $config);
                })->toArray();
            } elseif ($related instanceof \Illuminate\Database\Eloquent\Model) {
                $output[$relatedName] = [
                    'id' => $related->id,
                    'name' => $related->name ?? $related->title ?? $related->code ?? null,
                ];
            }
        }

        // Add URL
        $output['url'] = url($config['url_path'].'/'.$item->id);

        return $output;
    }

    /**
     * Format a related item for output.
     *
     * @return array<string, mixed>
     */
    protected function formatRelatedItem(mixed $item, array $parentConfig): array
    {
        $output = [
            'id' => $item->id,
        ];

        // Try common name fields
        foreach (['name', 'title', 'code'] as $field) {
            if (isset($item->{$field})) {
                $output[$field] = $item->{$field};
                break;
            }
        }

        // Add URL if we can determine the path
        $className = class_basename($item);
        $urlPath = Str::plural(Str::kebab($className));
        $output['url'] = url("/app/{$urlPath}/{$item->id}");

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
                ->description('The type of entity to retrieve. Options: '.implode(', ', EntityConfig::types()))
                ->required(),

            'id' => $schema->integer()
                ->description('The database ID of the entity.'),

            'code' => $schema->string()
                ->description('The unique code of the entity (for entities that have codes like standards, controls, policies).'),
        ];
    }
}
