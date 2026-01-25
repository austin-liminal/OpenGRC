<?php

namespace App\Filament\Resources\TrustCenterContentBlockResource\Pages;

use App\Filament\Resources\TrustCenterContentBlockResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrustCenterContentBlock extends ViewRecord
{
    protected static string $resource = TrustCenterContentBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
