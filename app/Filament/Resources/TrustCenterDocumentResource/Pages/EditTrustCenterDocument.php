<?php

namespace App\Filament\Resources\TrustCenterDocumentResource\Pages;

use App\Filament\Resources\TrustCenterDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditTrustCenterDocument extends EditRecord
{
    protected static string $resource = TrustCenterDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Get file size and mime type from the uploaded file if file changed
        if (isset($data['file_path']) && is_string($data['file_path'])) {
            $disk = setting('storage.driver', 'private');
            $storage = Storage::disk($disk);

            if ($storage->exists($data['file_path'])) {
                $data['file_size'] = $storage->size($data['file_path']);
                $data['mime_type'] = $storage->mimeType($data['file_path']);
            }
        }

        return $data;
    }
}
