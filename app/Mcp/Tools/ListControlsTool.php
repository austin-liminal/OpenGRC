<?php

namespace App\Mcp\Tools;

use App\Models\Control;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListControlsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Lists security controls from OpenGRC, optionally filtered by standard.

        Use this tool to:
        - Find controls to reference when creating policies
        - Understand what requirements exist in a compliance framework
        - Get control IDs for linking to policies

        Returns controls with their codes, titles, and descriptions.
        Use standard_id to filter controls from a specific compliance standard.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'standard_id' => 'nullable|integer|exists:standards,id',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Control::with('standard')
            ->orderBy('code');

        // Filter by standard
        if (! empty($validated['standard_id'])) {
            $query->where('standard_id', $validated['standard_id']);
        }

        // Search by code, title, or description
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;

        $controls = $query->paginate($perPage, ['*'], 'page', $page);

        $result = [
            'success' => true,
            'pagination' => [
                'current_page' => $controls->currentPage(),
                'per_page' => $controls->perPage(),
                'total' => $controls->total(),
                'last_page' => $controls->lastPage(),
            ],
            'controls' => $controls->map(function ($control) {
                return [
                    'id' => $control->id,
                    'code' => $control->code,
                    'title' => $control->title,
                    'description' => \Illuminate\Support\Str::limit(strip_tags($control->description), 300),
                    'type' => $control->type?->value ?? 'unknown',
                    'category' => $control->category?->value ?? 'unknown',
                    'standard' => [
                        'id' => $control->standard?->id,
                        'code' => $control->standard?->code,
                        'name' => $control->standard?->name,
                        'url' => $control->standard ? url("/app/standards/{$control->standard->id}") : null,
                    ],
                    'url' => url("/app/controls/{$control->id}"),
                ];
            })->toArray(),
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'standard_id' => $schema->integer()
                ->description('Filter controls by standard ID. Use ListStandards tool to get available standard IDs.'),

            'search' => $schema->string()
                ->description('Search term to filter controls by code, title, or description.'),

            'page' => $schema->integer()
                ->description('Page number for pagination (default: 1).'),

            'per_page' => $schema->integer()
                ->description('Number of controls per page (default: 20, max: 100).'),
        ];
    }
}
