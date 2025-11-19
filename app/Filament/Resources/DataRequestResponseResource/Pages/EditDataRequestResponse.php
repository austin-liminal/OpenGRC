<?php

namespace App\Filament\Resources\DataRequestResponseResource\Pages;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResponseResource;
use App\Models\DataRequestResponse;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDataRequestResponse extends EditRecord
{
    protected static string $resource = DataRequestResponseResource::class;

    protected bool $shouldSubmit = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Save Changes')
                ->submit(null)
                ->action(fn () => $this->save(false)),
            $this->getSubmitAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSubmitAction(): Actions\Action
    {
        return Actions\Action::make('submit')
            ->label('Submit Response')
            ->color('success')
            ->action(function () {
                $this->shouldSubmit = true;
                $this->save(false);
            })
            ->requiresConfirmation()
            ->modalHeading('Submit Response')
            ->modalDescription('Are you sure you want to submit this response? This will change the status to "Responded".')
            ->modalSubmitActionLabel('Yes, Submit');
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var DataRequestResponse $record */
        $record = parent::handleRecordUpdate($record, $data);

        // Only change status to RESPONDED if submitting
        if ($this->shouldSubmit) {
            $record->status = ResponseStatus::RESPONDED;
            $record->save();
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        // Only redirect to to-do page if submitting, otherwise stay on current page
        if ($this->shouldSubmit) {
            return route('filament.app.pages.to-do');
        }

        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
