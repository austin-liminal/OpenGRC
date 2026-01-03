<?php

namespace App\Mcp\Tools;

use App\Models\Policy;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListPoliciesTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Lists all policies in OpenGRC with their codes, names, and statuses.

        Use this tool to:
        - See existing policies and their codes to understand naming patterns
        - Find policies to reference or update
        - Get an overview of the policy landscape

        Returns a paginated list of policies sorted by code.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Policy::with(['status', 'scope', 'department', 'owner'])
            ->orderBy('code');

        // Filter by status if provided
        if (! empty($validated['status'])) {
            $query->whereHas('status', function ($q) use ($validated) {
                $q->where('name', $validated['status']);
            });
        }

        // Search by name or code
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;

        $policies = $query->paginate($perPage, ['*'], 'page', $page);

        $result = [
            'success' => true,
            'pagination' => [
                'current_page' => $policies->currentPage(),
                'per_page' => $policies->perPage(),
                'total' => $policies->total(),
                'last_page' => $policies->lastPage(),
            ],
            'policies' => $policies->map(function ($policy) {
                return [
                    'id' => $policy->id,
                    'code' => $policy->code,
                    'name' => $policy->name,
                    'status' => $policy->status?->name ?? 'Unknown',
                    'scope' => $policy->scope?->name,
                    'department' => $policy->department?->name,
                    'owner' => $policy->owner?->name,
                    'effective_date' => $policy->effective_date?->format('Y-m-d'),
                    'created_at' => $policy->created_at->format('Y-m-d'),
                    'url' => url("/app/policies/{$policy->id}"),
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
            'page' => $schema->integer()
                ->description('Page number for pagination (default: 1).'),

            'per_page' => $schema->integer()
                ->description('Number of policies per page (default: 20, max: 100).'),

            'status' => $schema->string()
                ->description('Filter by status name (e.g., "Draft", "Approved", "In Review").'),

            'search' => $schema->string()
                ->description('Search term to filter policies by code or name.'),
        ];
    }
}
