<?php

namespace App\Http\Controllers\API;

use App\Models\DataRequest;
use Illuminate\Http\Request;

class DataRequestController extends BaseApiController
{
    protected string $modelClass = DataRequest::class;

    protected string $resourceName = 'DataRequests';

    protected array $indexRelations = ['audit', 'auditItem'];

    protected array $showRelations = ['audit', 'auditItem', 'responses'];

    protected array $searchableFields = ['request_text'];

    protected array $sortableFields = ['id', 'status', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'audit_id' => 'nullable|exists:audits,id',
            'audit_item_id' => 'nullable|exists:audit_items,id',
            'request_text' => 'required|string',
            'status' => 'nullable|string',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'audit_id' => 'nullable|exists:audits,id',
            'audit_item_id' => 'nullable|exists:audit_items,id',
            'request_text' => 'sometimes|string',
            'status' => 'nullable|string',
        ]);
    }
}
