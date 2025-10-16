<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\ActionSize;

class ListRisks extends ListRecords
{
    protected static string $resource = RiskResource::class;

    protected ?string $heading = 'Risk Management';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Track New Risk'),
            Actions\Action::make('download_risk_report')
                ->label('Download Risk Report')
                ->icon('heroicon-o-document-arrow-down')
                ->size(ActionSize::Small)
                ->color('primary')
                ->action(function () {
                    // Get all risks with their implementations, sorted by residual risk
                    $risks = \App\Models\Risk::with(['implementations'])
                        ->get()
                        ->sortByDesc(function ($risk) {
                            return ($risk->residual_likelihood + $risk->residual_impact) / 2;
                        });

                    $pdf = Pdf::loadView('reports.risk-report', [
                        'risks' => $risks,
                    ]);

                    // Set to landscape orientation
                    $pdf->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        function () use ($pdf) {
                            echo $pdf->output();
                        },
                        'Risk-Report-'.date('Y-m-d').'.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RiskResource\Widgets\InherentRisk::class,
            RiskResource\Widgets\ResidualRisk::class,
        ];
    }
}
