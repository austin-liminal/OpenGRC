<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPolicyDetails extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_document')
                ->label('View Document')
                ->url(fn ($record) => route('filament.app.resources.policies.view', $record))
                ->icon('heroicon-o-document-text')
                ->color('gray'),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
