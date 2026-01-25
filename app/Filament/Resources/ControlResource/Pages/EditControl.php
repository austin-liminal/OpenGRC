<?php

namespace App\Filament\Resources\ControlResource\Pages;

use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Resources\ControlResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditControl extends EditRecord
{
    use HasTaxonomyFields;

    protected static string $resource = ControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Control';
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        parent::save($shouldRedirect);

        if ($shouldRedirect) {
            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }
    }
}
