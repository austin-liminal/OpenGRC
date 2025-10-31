<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRisk extends ViewRecord
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make('Update Risk')
                ->slideOver()
                ->using(function (Actions\EditAction $action, array $data, $record) {
                    // Calculate risk scores before saving
                    $data['inherent_risk'] = $data['inherent_likelihood'] * $data['inherent_impact'];
                    $data['residual_risk'] = $data['residual_likelihood'] * $data['residual_impact'];

                    // Update the record
                    $record->update($data);

                    return $record;
                })
                ->successNotificationTitle('Risk updated successfully')
                ->after(function () {
                    // Refresh the view page to show updated data
                    $this->fillForm();
                }),
        ];

    }
}
