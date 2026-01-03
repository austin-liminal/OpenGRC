<?php

namespace App\Mcp\Tools;

use App\Models\Standard;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListStandardsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Lists all compliance standards/frameworks in OpenGRC.

        Use this tool to:
        - Discover available standards (NIST, ISO, SOC2, etc.)
        - Find standards to reference when creating policies
        - Understand what compliance frameworks are tracked

        Returns all standards with their codes, names, and control counts.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $query = Standard::withCount('controls')
            ->orderBy('code');

        // Search by name or code
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('authority', 'like', "%{$search}%");
            });
        }

        $standards = $query->get();

        $result = [
            'success' => true,
            'total' => $standards->count(),
            'standards' => $standards->map(function ($standard) {
                return [
                    'id' => $standard->id,
                    'code' => $standard->code,
                    'name' => $standard->name,
                    'authority' => $standard->authority,
                    'status' => $standard->status?->value ?? 'unknown',
                    'description' => \Illuminate\Support\Str::limit(strip_tags($standard->description), 200),
                    'controls_count' => $standard->controls_count,
                    'reference_url' => $standard->reference_url,
                    'url' => url("/app/standards/{$standard->id}"),
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
            'search' => $schema->string()
                ->description('Optional search term to filter standards by code, name, or authority.'),
        ];
    }
}
