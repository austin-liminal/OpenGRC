<?php

namespace App\Http\Controllers\API;

use App\Models\Standard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StandardController extends BaseApiController
{
    protected string $modelClass = Standard::class;

    protected string $resourceName = 'Standards';

    protected array $indexRelations = [];

    protected array $showRelations = ['controls', 'programs'];

    protected array $searchableFields = ['code', 'title', 'description'];

    protected array $sortableFields = ['id', 'code', 'title', 'status', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:255|unique:standards,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('standards', 'code')->ignore($resource->id)],
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
    }
}
