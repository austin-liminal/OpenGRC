<?php

namespace App\Mcp\Tools;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetTaxonomyValuesTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'GetTaxonomyValues';

    /**
     * The tool's description.
     */
    protected string $description = 'Gets valid taxonomy values (policy-status, policy-scope, department) for policy fields.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'nullable|string|max:255',
        ]);

        // Default taxonomy types relevant to policies
        $defaultTypes = ['policy-status', 'policy-scope', 'department'];

        if (! empty($validated['type'])) {
            $types = [$validated['type']];
        } else {
            $types = $defaultTypes;
        }

        $result = [
            'success' => true,
            'taxonomies' => [],
        ];

        foreach ($types as $type) {
            $taxonomies = Taxonomy::where('type', $type)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $result['taxonomies'][$type] = $taxonomies->map(function ($taxonomy) {
                return [
                    'id' => $taxonomy->id,
                    'name' => $taxonomy->name,
                    'slug' => $taxonomy->slug,
                    'description' => $taxonomy->description,
                ];
            })->toArray();
        }

        return Response::text(json_encode($result, JSON_PRETTY_PRINT));
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
                ->description('The taxonomy type to retrieve. Common types: "policy-status", "policy-scope", "department". If not provided, returns all policy-related taxonomy types.'),
        ];
    }
}
