<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    public function getTitle(): string
    {
        return 'Program Details ('.$this->getRecord()->name.')';
    }

    public function getBreadcrumbs(): array
    {
        return [
            ProgramResource::getUrl() => 'Programs',
            $this->getRecord()->name,
            'View',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\EditAction::make(),
            \Filament\Pages\Actions\DeleteAction::make(),
        ];
    }
}
