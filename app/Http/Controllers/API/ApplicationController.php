<?php

namespace App\Http\Controllers\API;

use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends BaseApiController
{
    protected string $modelClass = Application::class;

    protected string $resourceName = 'Applications';

    protected array $indexRelations = ['vendor'];

    protected array $showRelations = ['vendor', 'applicationOwner'];

    protected array $searchableFields = ['name', 'description', 'vendor.name'];

    protected array $sortableFields = ['id', 'name', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vendor_id' => 'nullable|exists:vendors,id',
            'application_owner_id' => 'nullable|exists:users,id',
            'version' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
            'status' => 'nullable|string',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'vendor_id' => 'nullable|exists:vendors,id',
            'application_owner_id' => 'nullable|exists:users,id',
            'version' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
            'status' => 'nullable|string',
        ]);
    }
}
