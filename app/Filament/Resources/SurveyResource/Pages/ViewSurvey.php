<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\SurveyResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSurvey extends ViewRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('copy_link')
                ->label(__('survey.survey.actions.copy_link'))
                ->icon('heroicon-o-link')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Survey Link')
                ->modalDescription(fn () => 'Share this link with respondents: '.$this->record->getPublicUrl())
                ->modalSubmitActionLabel('Close')
                ->action(fn () => null)
                ->visible(fn () => $this->record->access_token !== null),
            Actions\Action::make('mark_complete')
                ->label(__('survey.survey.actions.mark_complete'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => SurveyStatus::COMPLETED,
                        'completed_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Survey marked as complete')
                        ->success()
                        ->send();
                })
                ->visible(fn () => ! in_array($this->record->status, [SurveyStatus::COMPLETED, SurveyStatus::EXPIRED])),
            Actions\Action::make('score_survey')
                ->label('Score Survey')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->url(fn () => SurveyResource::getUrl('score', ['record' => $this->record]))
                ->visible(fn () => $this->record->status === SurveyStatus::COMPLETED),
        ];
    }
}
