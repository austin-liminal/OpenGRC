<?php

namespace App\Mcp\Tools;

use App\Mcp\EntityConfig;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteEntityTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'DeleteEntity';

    /**
     * The tool's description.
     */
    protected string $description = 'Deletes a GRC entity. Most entities are soft-deleted and can be restored. Requires confirm=true parameter.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'confirm' => 'required|boolean',
        ]);

        if (! $validated['confirm']) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Delete operation not confirmed. Set confirm: true to proceed.',
            ], JSON_PRETTY_PRINT));
        }

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
        $entity = $modelClass::find($validated['id']);

        if (! $entity) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "{$config['label']} with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        // Get identifying info before deletion
        $nameField = $config['name_field'] ?? 'name';
        $name = $entity->{$nameField} ?? $entity->{$config['code_field']} ?? "#{$entity->id}";
        $entityId = $entity->id;

        try {
            // Check if model uses soft deletes
            $usesSoftDeletes = in_array(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($modelClass)
            );

            $entity->delete();

            return Response::text(json_encode([
                'success' => true,
                'message' => "{$config['label']} '{$name}' (ID: {$entityId}) has been deleted.",
                'soft_deleted' => $usesSoftDeletes,
                'restorable' => $usesSoftDeletes,
            ], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Failed to delete {$config['label']}: ".$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
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
                ->description('The type of entity to delete. Options: '.implode(', ', EntityConfig::types()))
                ->required(),

            'id' => $schema->integer()
                ->description('The database ID of the entity to delete.')
                ->required(),

            'confirm' => $schema->boolean()
                ->description('Must be set to true to confirm the deletion.')
                ->required(),
        ];
    }
}
