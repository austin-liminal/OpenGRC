<?php

namespace App\Filament\Resources\TrustCenterDocumentResource\Pages;

use App\Filament\Resources\TrustCenterDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewTrustCenterDocument extends ViewRecord
{
    protected static string $resource = TrustCenterDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('download')
                ->label(__('Download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $disk = setting('storage.driver', 'private');
                    $storage = Storage::disk($disk);

                    if ($storage->exists($this->record->file_path)) {
                        return $storage->download(
                            $this->record->file_path,
                            $this->record->file_name
                        );
                    }
                }),
        ];
    }
}
