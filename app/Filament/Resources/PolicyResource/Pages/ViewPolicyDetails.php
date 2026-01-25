<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPolicyDetails extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_document')
                ->label('View Document')
                ->url(fn ($record) => route('filament.app.resources.policies.view', $record))
                ->icon('heroicon-o-document-text')
                ->color('gray'),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
