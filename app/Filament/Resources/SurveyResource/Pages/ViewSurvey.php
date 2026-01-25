<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\SurveyResource;
use App\Filament\Resources\VendorResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSurvey extends ViewRecord
{
    protected static string $resource = SurveyResource::class;

    public function getBreadcrumbs(): array
    {
        // If this survey is associated with a vendor, navigate back to vendor
        if ($this->record->vendor_id) {
            return [
                VendorResource::getUrl() => __('Vendors'),
                VendorResource::getUrl('view', ['record' => $this->record->vendor_id]) => $this->record->vendor?->name ?? __('Vendor'),
                $this->record->display_title,
            ];
        }

        return parent::getBreadcrumbs();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('respond_internal')
                ->label(__('Complete Assessment'))
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->url(fn () => SurveyResource::getUrl('respond-internal', ['record' => $this->record]))
                ->visible(fn () => $this->record->isInternal()
                    && in_array($this->record->status, [SurveyStatus::DRAFT, SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
            Action::make('mark_complete')
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
            Action::make('score_survey')
                ->label('Score Survey')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->url(fn () => SurveyResource::getUrl('score', ['record' => $this->record]))
                ->visible(fn () => in_array($this->record->status, [SurveyStatus::PENDING_SCORING, SurveyStatus::COMPLETED])),
        ];
    }
}
