<?php

namespace App\Filament\Resources\DataRequestResource\Pages;

use App\Filament\Resources\DataRequestResource;
use App\Models\DataRequest;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditDataRequest extends EditRecord
{
    protected static string $resource = DataRequestResource::class;

    protected static ?string $title = 'View Data Request';

    protected function getHeaderActions(): array
    {
        /** @var DataRequest $record */
        $record = $this->record;

        $actions = [];

        // Add the reassign action from the resource
        $actions = array_merge($actions, DataRequestResource::getViewFormActions());

        if ($record->audit_item_id) {
            $actions[] = Action::make('back')
                ->label('Back to Audit Item')
                ->icon('heroicon-m-arrow-left')
                ->url(route('filament.app.resources.audit-items.edit', $record->audit_item_id));
        }

        return $actions;
    }

    public function form(Form $form): Form
    {
        return DataRequestResource::getEditForm($form);
    }
}
