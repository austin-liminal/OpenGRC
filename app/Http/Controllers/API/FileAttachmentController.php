<?php

namespace App\Http\Controllers\API;

use App\Models\FileAttachment;
use Illuminate\Http\Request;

class FileAttachmentController extends BaseApiController
{
    protected string $modelClass = FileAttachment::class;

    protected string $resourceName = 'FileAttachments';

    protected array $indexRelations = [];

    protected array $showRelations = [];

    protected array $searchableFields = ['filename', 'original_filename'];

    protected array $sortableFields = ['id', 'filename', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'filename' => 'required|string|max:255',
            'original_filename' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'mime_type' => 'nullable|string|max:255',
            'size' => 'nullable|integer',
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'filename' => 'sometimes|string|max:255',
            'original_filename' => 'sometimes|string|max:255',
            'path' => 'sometimes|string|max:255',
            'mime_type' => 'nullable|string|max:255',
            'size' => 'nullable|integer',
            'attachable_type' => 'sometimes|string',
            'attachable_id' => 'sometimes|integer',
        ]);
    }
}
