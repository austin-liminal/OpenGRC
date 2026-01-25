<?php

namespace App\Filament\Vendor\Resources\SurveyResource\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Vendor\Resources\SurveyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSurvey extends ViewRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('respond')
                ->label('Respond to Survey')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn () => RespondToSurvey::getUrl(['record' => $this->record]))
                ->visible(fn () => in_array($this->record->status, [
                    SurveyStatus::SENT,
                    SurveyStatus::IN_PROGRESS,
                ])),
        ];
    }
}
