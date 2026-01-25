<?php

namespace App\Filament\Admin\Resources\TaxonomyResource\Pages;

use App\Filament\Admin\Resources\TaxonomyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxonomy extends EditRecord
{
    protected static string $resource = TaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(fn () => 'Save '.($this->record->name ?? 'Taxonomy'));
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->record->name.' saved successfully';
    }
}
