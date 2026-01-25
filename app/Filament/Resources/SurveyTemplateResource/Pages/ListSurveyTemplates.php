<?php

namespace App\Filament\Resources\SurveyTemplateResource\Pages;

use App\Filament\Resources\SurveyTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSurveyTemplates extends ListRecords
{
    protected static string $resource = SurveyTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
