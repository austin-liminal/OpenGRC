<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use App\Services\VendorAssessmentService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assess_risk')
                ->label(__('Assess Risk'))
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->form(VendorAssessmentService::getAssessRiskFormSchema())
                ->action(fn (array $data) => VendorAssessmentService::handleAssessRisk($this->record, $data)),
            Actions\EditAction::make(),
        ];
    }
}
