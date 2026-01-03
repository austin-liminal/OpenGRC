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
class ListEntitiesTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Lists entities from OpenGRC with filtering and pagination.

        Supported entity types:
        - `standard`: Compliance frameworks (NIST, ISO, SOC2, etc.)
        - `control`: Security controls within standards
        - `implementation`: How controls are implemented
        - `policy`: Security and compliance policies
        - `risk`: Risk register entries
        - `program`: Organizational security programs
        - `audit`: Assessment/audit records
        - `audit_item`: Individual audit questions/items
        - `vendor`: Third-party vendors
        - `application`: Applications/systems
        - `asset`: IT assets

        Use the `type` parameter to specify which entity to list.
        Supports search, pagination, and filtering by related entities.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'filter' => 'nullable|array',
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
        $query = $modelClass::query();

        // Load relations for list view
        if (! empty($config['list_relations'])) {
            $query->with($config['list_relations']);
        }

        // Add counts
        if (! empty($config['list_counts'])) {
            $query->withCount($config['list_counts']);
        }

        // Apply search
        if (! empty($validated['search']) && ! empty($config['search_fields'])) {
            $search = $validated['search'];
            $searchFields = $config['search_fields'];
            $query->where(function ($q) use ($search, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // Apply filters
        if (! empty($validated['filter'])) {
            foreach ($validated['filter'] as $field => $value) {
                if (Str::endsWith($field, '_id')) {
                    $query->where($field, $value);
                }
            }
        }

        // Order by code if available, otherwise by name/title, otherwise by id
        if ($config['code_field']) {
            $query->orderBy($config['code_field']);
        } elseif ($config['name_field']) {
            $query->orderBy($config['name_field']);
        } else {
            $query->orderBy('id');
        }

        // Paginate
        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;
        $results = $query->paginate($perPage, ['*'], 'page', $page);

        // Format results
        $items = $results->map(function ($item) use ($config) {
            return $this->formatListItem($item, $config);
        })->toArray();

        $result = [
            'success' => true,
            'type' => $type,
            'label' => $config['label'],
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            $config['plural'] => $items,
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Format a single item for list output.
     *
     * @return array<string, mixed>
     */
    protected function formatListItem(mixed $item, array $config): array
    {
        $output = [];

        // Add base fields
        foreach ($config['list_fields'] as $field) {
            $value = $item->{$field};

            // Handle enums
            if (is_object($value) && method_exists($value, 'value')) {
                $value = $value->value;
            }

            // Handle dates
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            // Truncate long text fields
            if (is_string($value) && strlen($value) > 300) {
                $value = Str::limit(strip_tags($value), 300);
            }

            $output[$field] = $value;
        }

        // Add relation data
        foreach ($config['list_relations'] as $relation) {
            $related = $item->{$relation};
            if ($related) {
                $relatedName = Str::snake($relation);
                if ($related instanceof \Illuminate\Database\Eloquent\Model) {
                    $output[$relatedName] = [
                        'id' => $related->id,
                        'name' => $related->name ?? $related->title ?? $related->code ?? null,
                    ];
                }
            }
        }

        // Add counts
        foreach ($config['list_counts'] as $countRelation) {
            $countField = Str::snake($countRelation).'_count';
            $output[$countField] = $item->{$countField} ?? 0;
        }

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
                ->description('The type of entity to list. Options: '.implode(', ', EntityConfig::types()))
                ->required(),

            'search' => $schema->string()
                ->description('Search term to filter results by name, code, or other searchable fields.'),

            'page' => $schema->integer()
                ->description('Page number for pagination (default: 1).'),

            'per_page' => $schema->integer()
                ->description('Number of items per page (default: 20, max: 100).'),

            'filter' => $schema->object()
                ->description('Filter by related entity IDs. Example: {"standard_id": 1} to filter controls by standard.'),
        ];
    }
}
