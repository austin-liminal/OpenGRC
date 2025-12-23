<?php

namespace App\Filament\Vendor\Resources\SurveyResource\Pages;

use App\Filament\Vendor\Resources\SurveyResource;
use Filament\Resources\Pages\ListRecords;

class ListSurveys extends ListRecords
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
