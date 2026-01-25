<?php

namespace App\Filament\Resources\SurveyTemplateResource\Pages;

use App\Enums\SurveyTemplateStatus;
use App\Filament\Resources\SurveyResource;
use App\Filament\Resources\SurveyTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSurveyTemplate extends ViewRecord
{
    protected static string $resource = SurveyTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('create_survey')
                ->label(__('survey.template.actions.create_survey'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->url(fn () => SurveyResource::getUrl('create', ['template' => $this->record->id]))
                ->visible(fn () => $this->record->status === SurveyTemplateStatus::ACTIVE),
        ];
    }
}
