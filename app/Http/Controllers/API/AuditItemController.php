<?php

namespace App\Http\Controllers\API;

use App\Models\AuditItem;
use Illuminate\Http\Request;

class AuditItemController extends BaseApiController
{
    protected string $modelClass = AuditItem::class;

    protected string $resourceName = 'AuditItems';

    protected array $indexRelations = ['audit', 'auditable'];

    protected array $showRelations = ['audit', 'auditable', 'dataRequests'];

    protected array $searchableFields = ['notes'];

    protected array $sortableFields = ['id', 'status', 'effectiveness', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'audit_id' => 'required|exists:audits,id',
            'auditable_type' => 'required|string',
            'auditable_id' => 'required|integer',
            'status' => 'nullable|string',
            'effectiveness' => 'nullable|string',
            'applicability' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'audit_id' => 'sometimes|exists:audits,id',
            'auditable_type' => 'sometimes|string',
            'auditable_id' => 'sometimes|integer',
            'status' => 'nullable|string',
            'effectiveness' => 'nullable|string',
            'applicability' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
    }
}
