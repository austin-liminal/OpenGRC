<?php

namespace App\Filament\Resources\SurveyTemplateResource\Pages;

use App\Filament\Resources\SurveyTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSurveyTemplate extends EditRecord
{
    protected static string $resource = SurveyTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
