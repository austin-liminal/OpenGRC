<?php

namespace App\Mcp\Tools;

use App\Models\Policy;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetPolicyTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Retrieves a specific policy by its code or ID.

        Use this tool to:
        - Get full details of a policy including its content
        - Review existing policies before creating new ones
        - Find linked controls and implementations

        Returns the complete policy document with all fields.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'id' => 'nullable|integer',
        ], [
            'code' => 'You must provide either a policy code or ID.',
        ]);

        if (empty($validated['code']) && empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'You must provide either a policy code or ID.',
            ], JSON_PRETTY_PRINT));
        }

        $query = Policy::with([
            'status',
            'scope',
            'department',
            'owner',
            'controls',
            'implementations',
            'creator',
            'updater',
        ]);

        if (! empty($validated['code'])) {
            $policy = $query->where('code', $validated['code'])->first();
        } else {
            $policy = $query->find($validated['id']);
        }

        if (! $policy) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Policy not found.',
            ], JSON_PRETTY_PRINT));
        }

        $result = [
            'success' => true,
            'policy' => [
                'id' => $policy->id,
                'code' => $policy->code,
                'name' => $policy->name,
                'purpose' => $policy->purpose,
                'policy_scope' => $policy->policy_scope,
                'body' => $policy->body,
                'status' => $policy->status?->name ?? 'Unknown',
                'scope' => $policy->scope?->name,
                'department' => $policy->department?->name,
                'owner' => $policy->owner?->name,
                'effective_date' => $policy->effective_date?->format('Y-m-d'),
                'retired_date' => $policy->retired_date?->format('Y-m-d'),
                'revision_history' => $policy->revision_history,
                'controls' => $policy->controls->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'title' => $c->title,
                ])->toArray(),
                'implementations' => $policy->implementations->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name ?? $i->title ?? 'Implementation #' . $i->id,
                ])->toArray(),
                'created_by' => $policy->creator?->name,
                'updated_by' => $policy->updater?->name,
                'created_at' => $policy->created_at->toIso8601String(),
                'updated_at' => $policy->updated_at->toIso8601String(),
            ],
        ];

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
            'code' => $schema->string()
                ->description('The unique code of the policy (e.g., "POL-001").'),

            'id' => $schema->integer()
                ->description('The database ID of the policy.'),
        ];
    }
}
