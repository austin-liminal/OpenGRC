<?php

namespace App\Http\Controllers\API;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends BaseApiController
{
    protected string $modelClass = Program::class;

    protected string $resourceName = 'Programs';

    protected array $indexRelations = ['programManager'];

    protected array $showRelations = ['programManager', 'standards', 'controls'];

    protected array $searchableFields = ['name', 'description'];

    protected array $sortableFields = ['id', 'name', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'program_manager_id' => 'nullable|exists:users,id',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'program_manager_id' => 'nullable|exists:users,id',
        ]);
    }
}
