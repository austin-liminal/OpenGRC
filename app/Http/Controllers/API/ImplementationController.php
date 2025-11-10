<?php

namespace App\Http\Controllers\API;

use App\Models\Implementation;
use Illuminate\Http\Request;

class ImplementationController extends BaseApiController
{
    protected string $modelClass = Implementation::class;

    protected string $resourceName = 'Implementations';

    protected array $indexRelations = [];

    protected array $showRelations = ['controls', 'risks', 'assets', 'implementationOwner'];

    protected array $searchableFields = ['title', 'details', 'notes'];

    protected array $sortableFields = ['id', 'title', 'status', 'effectiveness', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'details' => 'nullable|string',
            'notes' => 'nullable|string',
            'test_procedure' => 'nullable|string',
            'status' => 'nullable|string',
            'effectiveness' => 'nullable|string',
            'implementation_owner_id' => 'nullable|exists:users,id',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'title' => 'sometimes|string|max:255',
            'details' => 'nullable|string',
            'notes' => 'nullable|string',
            'test_procedure' => 'nullable|string',
            'status' => 'nullable|string',
            'effectiveness' => 'nullable|string',
            'implementation_owner_id' => 'nullable|exists:users,id',
        ]);
    }
}
