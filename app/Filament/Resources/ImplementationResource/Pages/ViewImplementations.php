<?php

namespace App\Filament\Resources\ImplementationResource\Pages;

use App\Filament\Resources\ImplementationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewImplementations extends ViewRecord
{
    protected static string $resource = ImplementationResource::class;

    public function getTitle(): string
    {
        return 'Implementation Details ('.$this->getRecord()->code.')';
    }

    public function getBreadcrumbs(): array
    {
        return [
            ImplementationResource::getUrl() => 'Implementations',
            $this->getRecord()->code,
            'View',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make('Update Implementation')
                ->slideOver(),
        ];
    }
}
