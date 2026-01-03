<?php

namespace App\Mcp\Tools;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Policy;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreatePolicyTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Creates a new policy document in OpenGRC with structured fields.

        The tool will:
        - Auto-generate a unique policy code (e.g., POL-001, POL-002) if not provided
        - Set the status to 'Draft' by default
        - Support HTML content for policy_scope, purpose, and body fields
        - Optionally link the policy to controls by their IDs

        Use this tool when you need to create security policies, compliance documents,
        or governance policies in OpenGRC.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
            'policy_scope' => 'nullable|string',
            'body' => 'nullable|string',
            'effective_date' => 'nullable|date',
            'control_ids' => 'nullable|array',
            'control_ids.*' => 'integer|exists:controls,id',
        ], [
            'name.required' => 'Policy name is required.',
            'name.max' => 'Policy name must not exceed 255 characters.',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // Generate unique code if not provided
                $code = $validated['code'] ?? $this->generateUniqueCode();

                // Check if code already exists
                if (Policy::where('code', $code)->exists()) {
                    return Response::text(json_encode([
                        'success' => false,
                        'error' => "A policy with code '{$code}' already exists.",
                    ], JSON_PRETTY_PRINT));
                }

                // Get Draft status from taxonomy
                $draftStatus = Taxonomy::where('type', 'policy-status')
                    ->where('name', 'Draft')
                    ->first();

                // Create the policy
                $policy = Policy::create([
                    'code' => $code,
                    'name' => $validated['name'],
                    'purpose' => $validated['purpose'] ?? null,
                    'policy_scope' => $validated['policy_scope'] ?? null,
                    'body' => $validated['body'] ?? null,
                    'effective_date' => $validated['effective_date'] ?? null,
                    'status_id' => $draftStatus?->id,
                ]);

                // Attach controls if provided
                if (! empty($validated['control_ids'])) {
                    $policy->controls()->attach($validated['control_ids']);
                }

                // Load relationships for response
                $policy->load(['status', 'scope', 'controls']);

                return Response::text(json_encode([
                    'success' => true,
                    'message' => "Policy '{$policy->name}' created successfully.",
                    'policy' => [
                        'id' => $policy->id,
                        'code' => $policy->code,
                        'name' => $policy->name,
                        'purpose' => $policy->purpose,
                        'policy_scope' => $policy->policy_scope,
                        'body' => $policy->body,
                        'status' => $policy->status?->name ?? 'Draft',
                        'effective_date' => $policy->effective_date?->format('Y-m-d'),
                        'controls_count' => $policy->controls->count(),
                        'created_at' => $policy->created_at->toIso8601String(),
                    ],
                ], JSON_PRETTY_PRINT));
            });
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to create policy: ' . $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Generate a unique policy code following the POL-XXX pattern.
     */
    protected function generateUniqueCode(): string
    {
        // Find the highest existing POL-XXX code
        $lastPolicy = Policy::where('code', 'like', 'POL-%')
            ->orderByRaw("CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC")
            ->first();

        if ($lastPolicy && preg_match('/^POL-(\d+)$/', $lastPolicy->code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        // Format with leading zeros (e.g., POL-001, POL-012, POL-123)
        return sprintf('POL-%03d', $nextNumber);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The name/title of the policy (e.g., "Security Awareness Policy", "Access Control Policy").')
                ->required(),

            'code' => $schema->string()
                ->description('Optional unique identifier code for the policy. If not provided, one will be auto-generated following the POL-XXX pattern (e.g., POL-001).'),

            'purpose' => $schema->string()
                ->description('The purpose/objective of the policy. Supports HTML formatting. Example: "<p>The purpose of this policy is to establish guidelines for...</p>"'),

            'policy_scope' => $schema->string()
                ->description('The scope and applicability of the policy. Supports HTML formatting. Defines who/what the policy applies to.'),

            'body' => $schema->string()
                ->description('The main content/requirements of the policy. Supports HTML formatting with sections, lists, etc. This is where the detailed policy requirements go.'),

            'effective_date' => $schema->string()
                ->description('The date when the policy becomes effective (format: YYYY-MM-DD).'),

            'control_ids' => $schema->array()
                ->description('Optional array of control IDs to link this policy to existing security controls.'),
        ];
    }
}
