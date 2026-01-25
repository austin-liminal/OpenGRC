<?php

namespace App\Filament\Vendor\Resources\DocumentResource\Pages;

use App\Filament\Vendor\Resources\DocumentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('vendor.document.download', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
