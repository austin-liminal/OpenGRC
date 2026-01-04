<?php

namespace App\Mcp\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

/**
 * MCP Resource for taxonomy values.
 *
 * Provides read-only access to taxonomy lookup values such as
 * policy statuses, scopes, and departments.
 * URI format: opengrc://taxonomy/{type}
 *
 * @example opengrc://taxonomy/policy-status
 * @example opengrc://taxonomy/department
 */
#[Audience(Role::User)]
class TaxonomyResource extends Resource implements HasUriTemplate
{
    /**
     * The resource's name.
     */
    protected string $name = 'taxonomy';

    /**
     * The resource's description.
     */
    protected string $description = 'Gets valid taxonomy values (policy-status, policy-scope, department) for policy fields and other lookups.';

    /**
     * The resource's MIME type.
     */
    protected string $mimeType = 'application/json';

    /**
     * Get the URI template for this resource.
     */
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('opengrc://taxonomy/{type}');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $type = $request->get('type');

        $taxonomies = Taxonomy::where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($taxonomies->isEmpty()) {
            return Response::error("No taxonomy values found for type: {$type}");
        }

        $result = [
            'success' => true,
            'type' => $type,
            'values' => $taxonomies->map(function ($taxonomy) {
                return [
                    'id' => $taxonomy->id,
                    'name' => $taxonomy->name,
                    'slug' => $taxonomy->slug,
                    'description' => $taxonomy->description,
                ];
            })->toArray(),
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT));
    }
}
