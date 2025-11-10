<?php

namespace App\Http\Controllers\API;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends BaseApiController
{
    protected string $modelClass = Vendor::class;

    protected string $resourceName = 'Vendors';

    protected array $indexRelations = [];

    protected array $showRelations = [];

    protected array $searchableFields = ['name', 'description', 'contact_name', 'contact_email'];

    protected array $sortableFields = ['id', 'name', 'risk_level', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'risk_level' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'risk_level' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
    }
}
