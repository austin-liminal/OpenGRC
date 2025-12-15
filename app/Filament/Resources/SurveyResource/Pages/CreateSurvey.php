<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\SurveyResource;
use App\Mail\SurveyInvitationMail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateSurvey extends CreateRecord
{
    protected static string $resource = SurveyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Send invitation email if respondent email is provided
        if (! empty($this->record->respondent_email)) {
            // Update status to SENT regardless of email success
            $this->record->update(['status' => SurveyStatus::SENT]);

            try {
                Mail::send(new SurveyInvitationMail($this->record));

                Notification::make()
                    ->title(__('survey.survey.notifications.invitation_sent.title'))
                    ->body(__('survey.survey.notifications.invitation_sent.body', ['email' => $this->record->respondent_email]))
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title(__('Survey Created'))
                    ->body(__('Survey created but email notification failed: ').$e->getMessage())
                    ->warning()
                    ->send();
            }
        }
    }
}
