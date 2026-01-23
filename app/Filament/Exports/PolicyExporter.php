<?php

namespace App\Filament\Exports;

use App\Models\Policy;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PolicyExporter extends Exporter
{
    protected static ?string $model = Policy::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')
                ->label('Code'),
            ExportColumn::make('name')
                ->label('Name'),
            ExportColumn::make('document_type')
                ->label('Document Type')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? 'Other'),
            ExportColumn::make('status.name')
                ->label('Status'),
            ExportColumn::make('scope.name')
                ->label('Scope'),
            ExportColumn::make('department.name')
                ->label('Department'),
            ExportColumn::make('owner.name')
                ->label('Owner'),
            ExportColumn::make('effective_date')
                ->label('Effective Date'),
            ExportColumn::make('retired_date')
                ->label('Retired Date'),
            ExportColumn::make('policy_scope')
                ->label('Policy Scope'),
            ExportColumn::make('purpose')
                ->label('Purpose'),
            ExportColumn::make('body')
                ->label('Body'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your policy export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public function getFileDisk(): string
    {
        return setting('storage.driver', 'private');
    }
}
