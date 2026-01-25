<?php

namespace App\Filament\Resources\DataRequestResource\Pages;

use App\Filament\Resources\DataRequestResource;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewDataRequest extends ViewRecord
{
    protected static string $resource = DataRequestResource::class;

    protected static ?string $title = 'Data Request Viewer';

    public function form(Schema $schema): Schema
    {
        return DataRequestResource::getEditForm($schema);
    }

    /**
     * @property DataRequestResponse $record
     */
    protected function getHeaderActions(): array
    {
        /** @var DataRequest $record */
        $record = $this->record;

        $actions = [];

        // Add the accept, reject, reassign actions
        $actions = array_merge($actions, DataRequestResource::getPageFooterActions($record));

        if ($record->audit_item_id) {
            $actions[] = Action::make('back')
                ->label('Back to Audit Item')
                ->icon('heroicon-m-arrow-left')
                ->url(route('filament.app.resources.audit-items.edit', $record->audit_item_id));
        }

        return $actions;
    }
}
