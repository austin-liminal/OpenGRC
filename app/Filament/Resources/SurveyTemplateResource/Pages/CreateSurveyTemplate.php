<?php

namespace App\Filament\Resources\SurveyTemplateResource\Pages;

use App\Filament\Resources\SurveyTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSurveyTemplate extends CreateRecord
{
    protected static string $resource = SurveyTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();

        return $data;
    }
}
