<?php

namespace App\Filament\Resources\SurveyTemplateResource\Pages;

use App\Filament\Resources\SurveyTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSurveyTemplate extends EditRecord
{
    protected static string $resource = SurveyTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
