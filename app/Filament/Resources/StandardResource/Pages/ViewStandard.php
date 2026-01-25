<?php

namespace App\Filament\Resources\StandardResource\Pages;

use App\Filament\Resources\StandardResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStandard extends ViewRecord
{
    protected static string $resource = StandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
