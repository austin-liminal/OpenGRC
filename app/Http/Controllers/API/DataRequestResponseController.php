<?php

namespace App\Http\Controllers\API;

use App\Models\DataRequestResponse;
use Illuminate\Http\Request;

class DataRequestResponseController extends BaseApiController
{
    protected string $modelClass = DataRequestResponse::class;

    protected string $resourceName = 'DataRequestResponses';

    protected array $indexRelations = ['dataRequest', 'requestee'];

    protected array $showRelations = ['dataRequest', 'requestee'];

    protected array $searchableFields = ['response_text'];

    protected array $sortableFields = ['id', 'status', 'due_date', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'data_request_id' => 'required|exists:data_requests,id',
            'requestee_id' => 'required|exists:users,id',
            'response_text' => 'nullable|string',
            'status' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'data_request_id' => 'sometimes|exists:data_requests,id',
            'requestee_id' => 'sometimes|exists:users,id',
            'response_text' => 'nullable|string',
            'status' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);
    }
}
